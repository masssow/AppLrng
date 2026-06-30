# ACQ-DEV-FIX-001 — Correctifs et ajustements d'implémentation

> Écarts constatés entre les documents de démarrage et la réalité de l'implémentation.
> Ce document sert de référence pour éviter de répéter ces erreurs lors de nouvelles sessions.

---

## 1. Table `user` → réservée en PostgreSQL

**Problème** : `INSERT INTO user ...` lève une `SyntaxErrorException` car `user` est un mot réservé PostgreSQL.

**Correction** :
```php
// src/Domain/Shared/Entity/User.php
#[ORM\Table(name: 'users')]  // était: 'user'
```

**Migration** : `ALTER TABLE "user" RENAME TO users` (pas de DROP/CREATE pour préserver les FK).

---

## 2. Entité `Domaine` — champ `label` et non `nom`

**Problème** : Les templates appelaient `domaine.nom` mais l'entité expose `getLabel()`.

**Correction** dans les templates : `{{ p.domaine.label }}` (était `p.domaine.nom`)

**Correction** dans `ParcoursType` : `'choice_label' => 'label'` (était `'nom'`)

**Données de seed** :
```sql
INSERT INTO domaine (slug, label, icone, strategie_key) VALUES
  ('dev-web','Développement Web','💻','dev_web'),
  ('design','Design','🎨','design'),
  ('langues','Langues','🌍','langue'),
  ('comptabilite','Comptabilité','📊','comptabilite')
ON CONFLICT (slug) DO NOTHING;
```

---

## 3. Constructeur `User` — pas de `new User()` sans arguments

**Problème** : Le contrôleur d'inscription faisait `new User()` mais le constructeur exige `email`, `prenom`, `nom`, `niveau`.

**Correction** : `RegistrationFormType` avec `data_class: null`. Le contrôleur reconstruit l'entité manuellement :
```php
$user = new User(
    email:  $data['email'],
    prenom: $data['prenom'],
    nom:    $data['nom'],
    niveau: NiveauMaitrise::DEBUTANT,
);
```
Le niveau se configure ensuite **par parcours**, jamais à l'inscription.

---

## 4. Locale par défaut — `fr` et non `en`

**Correction** dans `config/packages/translation.yaml` :
```yaml
framework:
    default_locale: fr  # était: en
```
**Langues supportées** : `fr` et `es` uniquement (pas `en`).

---

## 5. Switcher de langue — route dédiée + session

**Solution** : route dédiée + stockage en session via `LocaleSubscriber`.

```php
// src/Http/Controller/LocaleController.php
#[Route('/langue/{locale}', name: 'app_set_locale')]
public function setLocale(string $locale, Request $request): Response
{
    if (in_array($locale, ['fr', 'es'], true)) {
        $request->getSession()->set('_locale', $locale);
    }
    return $this->redirect($request->headers->get('referer', '/'));
}
```
`LocaleSubscriber` lit `_locale` depuis la session (priority 20) sur chaque requête.

---

## 6. `findPendingForUser` — signature à 2 arguments

**Correction** : `$revisionRepository->findPendingForUser($user, new \DateTime())`

---

## 7. Package `symfony/validator` manquant

```bash
composer require symfony/validator
```

---

## 8. Routes — dossier `src/Http/Controller` et non `src/Controller`

**Correction** dans `config/routes.yaml` :
```yaml
controllers:
    resource:
        path: ../src/Http/Controller/
        namespace: App\Http\Controller
    type: attribute
```

---

## 9. StrategyResolver — tagged_iterator avec exclude

`DefaultStrategy` doit être exclue du tagged iterator (fallback uniquement) :
```yaml
App\Application\IA\Strategy\StrategyResolver:
    arguments:
        $strategies: !tagged_iterator { tag: 'app.ia.strategy', exclude: 'App\Application\IA\Strategy\DefaultStrategy' }
```

---

## 10. `IAGatewayInterface` — arguments à répéter dans services.yaml

Répéter `$groqApiKey` dans les deux entrées pour que l'autowiring fonctionne :
```yaml
App\Infrastructure\IA\Gateway\GroqAdapter:
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'

App\Application\IA\Port\IAGatewayInterface:
    class: App\Infrastructure\IA\Gateway\GroqAdapter
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'
```

---

## 11. UUID → string dans les Messages Messenger

**Règle absolue** : toujours caster `->getId()` lors du dispatch.
```php
new GenerationParcoursMessage((string) $parcours->getId())
new GenerationConsolidationMessage((string) $session->getId(), (string) $trace->getId())
new EvaluationReponseMessage(TYPE, (string) $entity->getId())
```

---

## 12. `symfony/doctrine-messenger` manquant

```bash
composer require symfony/doctrine-messenger
php bin/console messenger:setup-transports
```

---

## 13. Valeurs d'enum — toujours minuscules

Les enums Symfony/Doctrine ont des valeurs en **minuscules** (`'en_attente'`, `'pret'`, `'generation'`…).
Dans Twig, comparer avec les valeurs réelles :
```twig
{# FAUX #}
{% if session.statut.value == 'EN_ATTENTE' %}
{# CORRECT #}
{% if session.statut.value == 'en_attente' %}
```

---

## 14. `SessionConsolidation::getExercice()` — singulier

La session a **un seul exercice** (`getExercice(): ?Exercice`), pas une collection.
```twig
{# FAUX #}
{% for exercice in session.exercices %}
{# CORRECT #}
{% if session.exercice is not null %}
    {{ session.exercice.enonce }}
{% endif %}
```

---

## 15. `Question::__construct()` — ordre des arguments

Signature : `(SessionConsolidation $session, string $texte, int $ordre)`
```php
// FAUX — $questionDTO->type est une string, pas un int
new Question($session, $questionDTO->texte, $questionDTO->type)

// CORRECT
new Question($session, $questionDTO->texte, $index + 1)
```

---

## 16. `ExerciceDTO::$outilSuggere` — nom exact de la propriété

```php
// FAUX
$dto->exercice->outil

// CORRECT
$dto->exercice->outilSuggere
```
Et `Exercice` n'a pas de méthode `setCriteresReussite()` — ignorer ce champ du DTO.

---

## 17. Route `app_consolidation_soumettre_exercice` inexistante

La route n'existe pas. Le formulaire d'exercice POST sur `app_consolidation_show` avec `exercice_id` en champ caché.
Le contrôleur `show` gère les deux cas (`question_id` ou `exercice_id`) en POST.

---

## 18. Commentaires Twig — syntaxe `{# #}` et non `{{# #}}`

```twig
{# FAUX - syntaxe Handlebars #}
{{# Commentaire #}}

{# CORRECT - syntaxe Twig #}
{# Commentaire #}
```

---

## 19. Adaptateur IA — Groq (gratuit) à la place de Claude

En développement local, utiliser **Groq** (API gratuite, modèle `llama-3.3-70b-versatile`) :
- Clé sur [console.groq.com](https://console.groq.com)
- Format OpenAI-compatible (`Bearer` token, endpoint `/openai/v1/chat/completions`)
- Réponse : `choices[0].message.content` (≠ Claude : `content[0].text`)

`services.yaml` :
```yaml
App\Infrastructure\IA\Gateway\GroqAdapter:
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'

App\Application\IA\Port\IAGatewayInterface:
    class: App\Infrastructure\IA\Gateway\GroqAdapter
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'
```

---

## 20. Worker Messenger — doit tourner en continu en dev

Le bus Messenger est **asynchrone** : les messages ne sont pas traités automatiquement.
En dev, lancer dans un terminal dédié :
```bash
php bin/console messenger:consume async
```
En cas de message échoué :
```bash
php bin/console messenger:failed:show
php bin/console messenger:failed:retry <id> --force
```

---

## 21. Progression reste à 0% après terminerSession()

**Sprint 1 — 2026-06-30**

**Problème** : `ConsolidationService::terminerSession()` n'appelait `ProgressionCalculator::recalculer()` que si `scoreMoyen >= 3` (via `RessourceConsolideeEvent`). Si le worker n'avait pas encore évalué les réponses au moment du clic "Terminer", `$scores = []` → `scoreMoyen = 0` → aucun event dispatché → progression jamais mise à jour.

**Fix** : appel inconditionnel à `recalculer()` à la fin de `terminerSession()`, après le bloc if/else.

```php
// Toujours recalculer — même score < 3
$this->progressionCalculator->recalculer($ressource->getParcours());
```

**Fichier** : `src/Application/Consolidation/Service/ConsolidationService.php`

---

## 22. Enums comparés en MAJUSCULES dans Twig

**Sprint 1 — 2026-06-30**

**Problème** : `parcours/show.html.twig` comparait `statut.value == 'ACTIF'` → badges toujours gris car les valeurs réelles des enums PHP sont minuscules (`'actif'`, `'brouillon'`, `'consolidee'`…).

**Fix** : toutes les comparaisons Twig passées en minuscules.

**Fichier** : `templates/parcours/show.html.twig`

**Rappel règle** : les backed enums de l'app ont **toujours des valeurs minuscules**. Dans Twig : `{% if statut.value == 'actif' %}` jamais `'ACTIF'`.

---

## 23. Écran ressource — affichage non contextuel

**Sprint 1 — 2026-06-30**

**Problème** : `ressource/show.html.twig` affichait toujours les mêmes boutons quel que soit le statut (A_FAIRE, EN_COURS, VUE, CONSOLIDEE). Pas de navigation retour. Aucun résultat de consolidation affiché.

**Fix** :
- `RessourceController::show()` passe maintenant `$derniereSession` et `$sessionEnCours` à la vue
- Template réécrit avec 4 blocs `{% if statut == '...' %}` distincts
- Breadcrumb `← [Titre du parcours]` ajouté en haut
- État CONSOLIDEE affiche score étoiles + aperçu feedbacks + lien révisions
- État VUE affiche le CTA "Consolider maintenant" ou lien vers session en cours

**Fichiers** : `templates/ressource/show.html.twig`, `src/Http/Controller/RessourceController.php`

**Import ajouté au controller** : `SessionConsolidationRepositoryInterface`

---

## 24. Navigation bloquante — consolidation/show sans retour arrière

**Sprint 1 — 2026-06-30**

**Problème** : `consolidation/show.html.twig` n'avait aucun lien de retour. L'utilisateur était bloqué sur la page.

**Fix** : lien `← [titre de la ressource]` ajouté en haut du template, pointant vers `app_ressource_show`.

**Fichier** : `templates/consolidation/show.html.twig`

**Note** : `consolidation/initier.html.twig` avait déjà ce lien — pas de modification nécessaire.

---

## 25. Nouvelles clés de traduction — écran ressource

**Sprint 1 — 2026-06-30**

Clés ajoutées dans `messages.fr.yaml` et `messages.es.yaml` sous la section `ressource:` :

| Clé | FR | ES |
|---|---|---|
| `ressource.open_link` | Ouvrir la ressource | Abrir el recurso |
| `ressource.pomodoros_suggeres` | pomodoros suggérés | pomodoros sugeridos |
| `ressource.pomodoro_tip` | séances de 25 min de concentration | sesiones de 25 min de concentración |
| `ressource.pomodoros_plan` | Plan de session | Plan de sesión |
| `ressource.start_cta` | Démarrer l'étude | Empezar el estudio |
| `ressource.consolidate_now` | Consolider maintenant | Consolidar ahora |
| `ressource.session_active` | Une session est en cours | Hay una sesión en curso |
| `ressource.voir_session` | Voir la session → | Ver la sesión → |
| `ressource.vue_ready` | Ressource vue — prête à consolider ! | ¡Recurso visto — listo para consolidar! |
| `ressource.consolidee_label` | Ressource consolidée ✓ | Recurso consolidado ✓ |
| `ressource.consolidee_le` | Consolidée le %date% | Consolidado el %date% |
| `ressource.revision_planifiee` | Révision espacée planifiée | Revisión espaciada planificada |
| `ressource.voir_revisions` | Voir mes révisions | Ver mis revisiones |
| `ressource.redo_consolidation` | Refaire une consolidation | Repetir la consolidación |

---

## 26. Progression 0% — cause racine : passerConsolidee() conditionnel

**Sprint 1.5 — 2026-06-30**

**Problème** : Le fix Sprint 1 (appel inconditionnel à `recalculer()`) était insuffisant. `recalculer()` ne compte que les ressources avec statut `CONSOLIDEE`. Or `passerConsolidee()` était lui-même conditionné à `scoreMoyen >= 3`. Si le worker n'avait pas encore évalué les réponses, les `feedbackScore` étaient null → `$scores = []` → `scoreMoyen = 0` → `passerConsolidee()` jamais appelé → ressource reste `VUE` → progression = 0%.

**Fix** : `passerConsolidee()` et `RessourceConsolideeEvent` appelés inconditionnellement à la fin de chaque session terminée. Le score < 3 ajoute toujours la ressource aux sujets fragiles mais ne bloque plus la transition de statut.

**Fichier** : `src/Application/Consolidation/Service/ConsolidationService.php`

---

## 27. Section "Sujets abordés" dans parcours/show

**Sprint 1.5 — 2026-06-30**

**Problème** : La page `parcours/show` n'affichait aucun aperçu du contenu des sessions de consolidation. L'utilisateur ne pouvait pas voir quels sujets avaient été couverts.

**Fix** :
- `ParcoursController::show()` injecte `SessionConsolidationRepositoryInterface`, construit une map `ressourceId → SessionConsolidation` pour toutes les ressources CONSOLIDEE
- `templates/parcours/show.html.twig` : chaque carte ressource CONSOLIDEE affiche un `<details>` collapsible listant les questions posées lors de la dernière session

**Fichiers** : `src/Http/Controller/ParcoursController.php`, `templates/parcours/show.html.twig`

---

## 28. Projet/show — enums MAJUSCULES (boutons invisibles)

**Sprint 1.5 — 2026-06-30**

**Problème** : `templates/projet/show.html.twig` comparait `etape.statut.value == 'DISPONIBLE'` et `'EN_COURS'`. Les valeurs réelles des backed enums PHP sont minuscules → boutons Démarrer/Soumettre jamais affichés, utilisateur bloqué.

**Fix** : comparaisons corrigées en `'disponible'` et `'en_cours'`.

**Fichier** : `templates/projet/show.html.twig`

---

## 29. Projet/show — navigation et états visuels des étapes

**Sprint 1.5 — 2026-06-30**

**Problème** : `templates/projet/show.html.twig` n'avait pas de lien retour vers le parcours. Les étapes verrouillées avaient la même apparence que les étapes disponibles — aucun signal visuel de leur statut.

**Fix** :
- Ajout lien `← [parcours.titre]` en haut de page (via `projet.parcours.id`)
- Chaque carte micro-étape affiche maintenant une icône selon le statut : `✓` (complete), `🔒` (verrouillee), `▶` (en_cours), `○` (disponible)
- Étapes verrouillées : opacité réduite (`opacity-50`)
- Étapes complètes : fond vert subtil

**Fichier** : `templates/projet/show.html.twig`

---

## 30. Dashboard — refonte en 4 zones priorisées + DashboardService

**Sprint 1 Dashboard — 2026-06-30**

Remplacement du tableau de bord minimal par une structure priorisée. Aucune nouvelle entité Doctrine ni migration.

### Architecture

**Nouveaux fichiers** :
- `src/Application/Dashboard/DTO/ReprendreDTO.php` — DTO contenant `type`, `titre`, `route`, `routeParams`, `contexte`
- `src/Application/Dashboard/DTO/ScoreAgregeDTO.php` — DTO contenant `valeur` (int) et `libelleQualitatif` (string)
- `src/Application/Dashboard/Service/DashboardService.php` — 5 méthodes publiques :
  - `getActionAReprendre(User): ?ReprendreDTO` — cascade : SessionConsolidation PRET → MicroEtape EN_COURS → Ressource EN_COURS/VUE
  - `getRevisionsDuJour(User): RevisionSpacee[]`
  - `getStreak(User): int` — jours consécutifs (60j, tolérance 1j)
  - `getScoreAgrege(User): ?ScoreAgregeDTO` — moyenne pondérée par `ressourcesTotal`
  - `getParcoursActifs(User): Parcours[]`
- `tests/Unit/Service/DashboardServiceTest.php` — 9 tests PHPUnit

**Méthodes ajoutées aux repositories** :

| Interface | Méthode | Utilisation |
|---|---|---|
| `SessionConsolidationRepositoryInterface` | `findPretPourUser(User)` | Zone Reprendre : cascade #1 |
| `SessionConsolidationRepositoryInterface` | `findDatesCompletionPourUser(User, DateTime)` | Streak |
| `MicroEtapeRepositoryInterface` | `findEnCoursPourUser(User)` | Zone Reprendre : cascade #2 |
| `MicroEtapeRepositoryInterface` | `findDatesCompletionPourUser(User, DateTime)` | Streak |
| `RevisionSpaceeRepositoryInterface` | `findDatesCompletionPourUser(User, DateTime)` | Streak |

**Template** : `templates/dashboard/index.html.twig` réécrit avec 4 zones :
1. Reprendre → encart violet avec CTA direct
2. Révisions du jour → retard en orange, jour prévu en neutre
3. Progression globale → libellé qualitatif + barre + streak
4. Mes parcours → liste existante repositionnée en bas

**Libellés qualitatifs** : `>= 80` → "Excellente maîtrise" / `>= 60` → "Bonne progression" / `>= 35` → "En cours de construction" / `< 35` → "Tout début de parcours"

---

## Checklist post-installation

```bash
# 1. Dépendances
composer require symfony/security-bundle symfony/twig-bundle symfony/form \
    symfony/translation symfony/messenger symfony/http-client symfony/validator \
    symfony/doctrine-messenger

# 2. .env.local
DATABASE_URL="postgresql://mg_admin:Passpass@127.0.0.1:5432/acquis_db?serverVersion=16&charset=utf8"
GROQ_API_KEY=gsk_...
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
MESSENGER_FAILED_TRANSPORT_DSN=doctrine://default?queue_name=failed&auto_setup=0

# 3. Migrations
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Setup transports Messenger
php bin/console messenger:setup-transports

# 5. Seed domaines
php bin/console doctrine:query:sql "INSERT INTO domaine (slug, label, icone, strategie_key) VALUES \
  ('dev-web','Développement Web','💻','dev_web'), \
  ('design','Design','🎨','design'), \
  ('langues','Langues','🌍','langue'), \
  ('comptabilite','Comptabilité','📊','comptabilite') \
  ON CONFLICT (slug) DO NOTHING"

# 6. Worker IA (terminal dédié)
php bin/console messenger:consume async
```
