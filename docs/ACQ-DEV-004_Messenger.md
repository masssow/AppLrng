# ACQ-DEV-004 — Symfony Messenger et flows asynchrones
**Massagrafik — Juin 2026**

---

## 1. Décisions figées

- **D1** : Tous les appels IA longs (3-10s) passent par Messenger — jamais en HTTP synchrone.
- **D2** : Messages = IDs uniquement. Le handler recharge les entités depuis la base.
- **D3** : Transport Doctrine (PostgreSQL) en V1. Pas de Redis/RabbitMQ.
- **D4** : failure_transport configuré explicitement.
- **D5** : Erreurs temporaires → retry. Erreurs fonctionnelles → ERREUR + UnrecoverableMessageHandlingException.
- **D6** : ConsolidationPreteEvent ≠ RessourceConsolideeEvent — ne pas confondre.
- **D7** : Polling UI toutes les 2s. Timeout 60s = attente prolongée (pas erreur serveur).
- **D8** : Handlers minces — rechargent entités, appellent Services, dispatchent events.

---

## 2. Les 3 messages

### GenerationConsolidationMessage
```php
// Déclenché : soumission TraceApprentissage
readonly class GenerationConsolidationMessage {
    public function __construct(
        public string $sessionConsolidationId,
        public string $traceApprentissageId
    ) {}
}
// Handler recharge : SessionConsolidation, TraceApprentissage, Ressource, Parcours, Domaine
```

### GenerationParcoursMessage
```php
// Déclenché : soumission liste de ressources
readonly class GenerationParcoursMessage {
    public function __construct(
        public string $parcoursId
    ) {}
}
// Handler recharge : Parcours + Ressources depuis la base
```

### EvaluationReponseMessage
```php
// Déclenché : soumission réponse question ou rendu exercice
readonly class EvaluationReponseMessage {
    public function __construct(
        public string $targetType,  // TYPE_QUESTION | TYPE_EXERCICE
        public string $targetId
    ) {}
}
// Handler recharge Question ou Exercice selon targetType
```

---

## 3. Flows complets

### Flow GenerationConsolidation
```
1. POST soumission TraceApprentissage
2. ConsolidationService::initier()
   → crée TraceApprentissage
   → crée SessionConsolidation (statut: EN_ATTENTE)
   → dispatch GenerationConsolidationMessage(sessionId, traceId)
3. Réponse HTTP immédiate → /consolidation/session/{sessionId}
4. Page en attente avec auto-refresh 5s
5. Worker :
   → SessionConsolidation statut → GENERATION
   → AIOrchestrator::genererConsolidation()
   → AIResponseParser + DTOValidator
   → persiste Questions + Exercice
   → SessionConsolidation statut → PRET
   → dispatch ConsolidationPreteEvent
6. Auto-refresh détecte PRET → affiche questions/exercice
```

### Flow GenerationParcours
```
1. POST soumission liste ressources
2. ParcoursService::initier()
   → crée Parcours (BROUILLON) + Ressources
   → dispatch GenerationParcoursMessage(parcoursId)
3. Réponse immédiate → /parcours/{id}
4. Worker :
   → réordonne Ressources
   → crée ProjetFilRouge + MicroEtapes
   → Parcours → ACTIF
   → dispatch ParcoursStructureEvent
5. Statut "Brouillon" affiché avec indicateur IA processing
```

### Flow EvaluationReponse
```
1. Utilisateur soumet réponse
2. ConsolidationService::soumettreReponseQuestion()
   → Question.reponseUtilisateur persistée
   → Question.statutEvaluation = EVALUATION
   → dispatch EvaluationReponseMessage(TYPE_QUESTION, questionId)
3. Réponse immédiate
4. Worker :
   → recharge Question depuis base
   → AIOrchestrator::evaluerReponse()
   → Question.feedbackIa + feedbackScore + decision
   → Question.statutEvaluation = EVALUEE
```

---

## 4. Configuration

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 2
                    delay: 2000
                    multiplier: 2
                    max_delay: 10000
            failed:
                dsn: '%env(MESSENGER_FAILED_TRANSPORT_DSN)%'

        routing:
            'App\Application\Messenger\Message\GenerationConsolidationMessage': async
            'App\Application\Messenger\Message\GenerationParcoursMessage': async
            'App\Application\Messenger\Message\EvaluationReponseMessage': async

when@test:
    framework:
        messenger:
            transports:
                async: 'in-memory://'
```

---

## 5. Gestion des erreurs

| Famille | Exemples | Action |
|---|---|---|
| Temporaire | Timeout, API down, quota, réseau | `throw $e` → Messenger retry |
| Définitive | JSON invalide, DTO incomplet | Statut ERREUR + UnrecoverableMessageHandlingException |

```
Tentative 1 → échec → retry 2s
Tentative 2 → échec → retry 4s
Tentative 3 → échec définitif → failed transport
```

---

## 6. Events dispatchés par les handlers

```
ConsolidationPreteEvent   → questions + exercice générés (statut PRET)
                           ≠ ressource consolidée
RessourceConsolideeEvent  → utilisateur a terminé + score >= 3/5
                           → ProgressionSubscriber + RevisionSubscriber
ParcoursStructureEvent    → ProjetFilRouge créé, Parcours ACTIF
                           → ProgressionSubscriber (initialisation)
```

---

## 7. Supervisor (prod)

```ini
[program:acquis_messenger]
command=php /path/to/bin/console messenger:consume async --time-limit=3600 --memory-limit=128M -vv
autostart=true
autorestart=true
numprocs=1
```

```bash
# Commandes utiles
php bin/console messenger:consume async -vv          # dev sans supervisor
php bin/console messenger:failed:show                # voir les échecs
php bin/console messenger:failed:show <id> -vv       # détail d'un échec
php bin/console messenger:failed:retry <id> --force  # rejouer un message
```

---

## Ajustements V1 — Implémentation réelle

### Package supplémentaire requis

```bash
composer require symfony/doctrine-messenger
```

### DSN avec `auto_setup=0` (recommandé prod)

```env
# .env.local
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
MESSENGER_FAILED_TRANSPORT_DSN=doctrine://default?queue_name=failed&auto_setup=0
```

La table `messenger_messages` ne se crée pas automatiquement — à exécuter une fois :
```bash
php bin/console messenger:setup-transports
```

### UUID → string obligatoire dans tous les Messages

Les IDs retournés par `->getId()` sont des objets `UuidV4`, pas des strings. Les Messages ont `public string $id` → TypeError si non casté.

```php
// RÈGLE ABSOLUE — toujours caster
new GenerationParcoursMessage((string) $parcours->getId())
new GenerationConsolidationMessage((string) $session->getId(), (string) $trace->getId())
new EvaluationReponseMessage(TYPE, (string) $entity->getId())
```

### Polling UI : meta-refresh (pas AlpineJS en V1)

La spec prévoyait AlpineJS toutes les 2s + endpoint JSON `/consolidation/statut/{id}`. En V1, implémenté avec une balise HTML plus simple :

```twig
{# Dans consolidation/show.html.twig — si statut en_attente ou generation #}
<meta http-equiv="refresh" content="5">
```

Rechargement page complet toutes les 5s. Upgrade AlpineJS prévu en V2 (voir `evol.md`).

### `messenger:failed:retry --all` n'existe pas en Symfony 7

```bash
# FAUX
php bin/console messenger:failed:retry --all

# CORRECT — passer les IDs explicitement
php bin/console messenger:failed:retry 7 8 9 --force
```

### Route exercice — pas de route dédiée en V1

La spec prévoyait `/exercice/{id}/rendu`. En V1, le formulaire d'exercice POST sur `app_consolidation_show` avec un champ caché :

```html
<input type="hidden" name="exercice_id" value="{{ session.exercice.id }}">
```

Le contrôleur `show` détecte `exercice_id` vs `question_id` pour router vers le bon service.
