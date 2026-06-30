# ACQ-DEV-001 — Vision Technique Acquis
**Massagrafik — Juin 2026**

---

## 1. Stack technique retenue

| Composant | Technologie | Raison |
|---|---|---|
| Backend | Symfony 7 / PHP 8.2 | Maîtrise existante, robustesse |
| Frontend | Twig + AlpineJS | Léger, pas de SPA en V1 |
| CSS | Tailwind CSS | Productivité UI |
| Base de données | PostgreSQL | Richesse de requêtes, JSON natif |
| IA | Claude API (Anthropic) | Génération questions, exercices, structure |
| Queue | Symfony Messenger | Appels IA asynchrones |
| Déploiement | Coolify (self-hosted) | Contrôle total, coûts maîtrisés |

---

## 2. Architecture en 4 couches

```
Domain        → Métier pur, aucune dépendance externe
Application   → Cas d'usage, orchestration, ports applicatifs
Infrastructure→ Implémentations techniques : Doctrine, Claude, sources
Http          → Interface web : controllers, forms, voters
```

### Règle de dépendance
```
Domain ne dépend de rien.
Application dépend de Domain.
Infrastructure implémente les ports de Domain et Application.
Http dépend de Application.
```

### 2.1 Architecture en 4 couches — Arborescence complète

```
src/
│
├── Domain/                    → Métier pur, aucune dépendance externe
│   ├── Parcours/
│   │   ├── Entity/Parcours.php
│   │   ├── Repository/ParcoursRepositoryInterface.php
│   │   ├── Event/ParcoursStructureEvent.php
│   │   ├── Event/ProjetTermineEvent.php
│   │   └── Enum/StatutParcours.php
│   ├── Ressource/
│   │   ├── Entity/Ressource.php
│   │   ├── Repository/RessourceRepositoryInterface.php
│   │   └── Enum/StatutRessource.php, TypeRessource.php
│   ├── Consolidation/
│   │   ├── Entity/TraceApprentissage.php, SessionConsolidation.php
│   │   ├── Entity/Question.php, Exercice.php
│   │   ├── Repository/TraceApprentissageRepositoryInterface.php
│   │   ├── Repository/SessionConsolidationRepositoryInterface.php
│   │   ├── Event/ConsolidationPreteEvent.php
│   │   ├── Event/RessourceConsolideeEvent.php
│   │   └── Enum/StatutSession.php, StatutEvaluation.php, TypeSession.php
│   ├── Projet/
│   │   ├── Entity/ProjetFilRouge.php, MicroEtape.php
│   │   ├── Repository/ProjetFilRougeRepositoryInterface.php
│   │   └── Enum/StatutProjet.php, StatutEtape.php, TypeMicroEtape.php
│   ├── Revision/
│   │   ├── Entity/RevisionSpacee.php
│   │   └── Repository/RevisionSpaceeRepositoryInterface.php
│   ├── Progression/
│   │   ├── Entity/Progression.php
│   │   └── Repository/ProgressionRepositoryInterface.php
│   └── Shared/
│       ├── Entity/User.php, Domaine.php
│       ├── Repository/UserRepositoryInterface.php
│       └── Enum/NiveauMaitrise.php, ModeAccompagnement.php, PlanUtilisateur.php
│
├── Application/                → Cas d'usage, orchestration, ports applicatifs
│   ├── Parcours/Service/ParcoursService.php
│   ├── Ressource/Service/RessourceService.php
│   ├── Consolidation/Service/ConsolidationService.php
│   ├── Projet/Service/MicroEtapeService.php
│   ├── Revision/
│   │   ├── Service/RevisionService.php
│   │   └── EventSubscriber/RevisionSubscriber.php
│   ├── Progression/
│   │   ├── Service/ProgressionCalculator.php
│   │   └── EventSubscriber/ProgressionSubscriber.php
│   ├── Security/OwnershipChecker.php
│   ├── IA/
│   │   ├── AIOrchestrator.php
│   │   ├── PromptBuilder.php
│   │   ├── PromptVersions.php
│   │   ├── Port/IAGatewayInterface.php
│   │   ├── DTO/PromptDTO.php, RawAIResponse.php
│   │   ├── DTO/ParcoursStructureDTO.php, ConsolidationDTO.php
│   │   ├── DTO/EvaluationDTO.php, QuestionDTO.php, ExerciceDTO.php
│   │   ├── Strategy/DomainPromptStrategyInterface.php
│   │   ├── Strategy/DevWebStrategy.php, DesignStrategy.php
│   │   ├── Strategy/LangueStrategy.php, ComptabiliteStrategy.php
│   │   ├── Strategy/DefaultStrategy.php, StrategyResolver.php
│   │   ├── Validator/AIResponseDTOValidator.php
│   │   └── Enum/PromptType.php
│   └── Messenger/
│       ├── Message/GenerationConsolidationMessage.php
│       ├── Message/GenerationParcoursMessage.php
│       ├── Message/EvaluationReponseMessage.php
│       ├── Handler/GenerationConsolidationHandler.php
│       ├── Handler/GenerationParcoursHandler.php
│       └── Handler/EvaluationReponseHandler.php
│
├── Infrastructure/             → Implémentations techniques
│   ├── Persistence/Doctrine/Repository/
│   │   ├── ParcoursRepository.php
│   │   ├── RessourceRepository.php
│   │   ├── TraceApprentissageRepository.php
│   │   ├── SessionConsolidationRepository.php
│   │   ├── ProjetFilRougeRepository.php
│   │   ├── MicroEtapeRepository.php
│   │   ├── RevisionSpaceeRepository.php
│   │   ├── ProgressionRepository.php
│   │   └── UserRepository.php
│   └── IA/
│       ├── Gateway/ClaudeAdapter.php
│       ├── Gateway/MockIAGateway.php
│       └── Parser/AIResponseParser.php
│
└── Http/                       → Interface web
    ├── Controller/
    │   ├── RegistrationController.php, LoginController.php
    │   ├── ParcoursController.php
    │   ├── RessourceController.php
    │   ├── ConsolidationController.php
    │   ├── ProjetController.php
    │   ├── RevisionController.php
    │   ├── EvaluationSurpriseController.php
    │   └── DashboardController.php
    ├── Form/
    │   ├── RegistrationFormType.php
    │   ├── ParcoursType.php
    │   └── TraceApprentissageType.php
    ├── Security/
    │   ├── AppAuthenticator.php
    │   └── Voter/ParcoursVoter.php, RessourceVoter.php
    │       └── Voter/SessionConsolidationVoter.php, TraceApprentissageVoter.php
    │           └── Voter/ProjetFilRougeVoter.php, MicroEtapeVoter.php
    │               └── Voter/RevisionSpaceeVoter.php
    └── EventSubscriber/LocaleSubscriber.php
```

### 2.2 Couche Service — règle absolue
Zéro logique métier dans les Controllers. Un controller valide la requête, appelle un Service, retourne une Response.

### 2.3 Event / Subscriber
Les domaines ne s'appellent pas directement. Ils émettent des événements.
- `RessourceConsolideeEvent` → ProgressionSubscriber + RevisionSubscriber
- `ProjetTermineEvent` → ProgressionSubscriber
- `ParcoursStructureEvent` → ProgressionSubscriber (initialisation)

### 2.4 Strategy pattern — consolidation par domaine
```php
interface DomainPromptStrategyInterface {
    public function supportsDomaine(string $key): bool;
    public function getSystemContext(): string;
    public function getConsolidationGuidelines(): string;
    public function getEvaluationGuidelines(): string;
    public function getExerciseGuidelines(): string;
    public function getSuggestedTools(): array;
}
```
Implémentations : DevWebStrategy, DesignStrategy, LangueStrategy, ComptabiliteStrategy, DefaultStrategy

---

## 3. Modèle de données — Vue macro

```
User
  Parcours          (objectif, duree, domaine, statut, derniere_eval_surprise_at)
    Ressource       (titre, type, url, ordre, statut, viewed_at, consolidated_at,
                     duree_estimee_minutes, pomodoros_suggeres)
      TraceApprentissage  (compris, points_flous, confiance 1-5, pomodoros_effectues)
      SessionConsolidation  (statut, prompt_version, parent_session?)
        Question    (texte, reponse_user, feedback_ia, decision, statut_evaluation)
        Exercice    (enonce, outil_suggere, rendu_user, feedback_ia, statut_evaluation)
      RevisionSpacee        (iteration, date_prevue, score, completee_at)
    ProjetFilRouge  (titre, description, statut)
      MicroEtape    (titre, type, outil_externe?, statut, ordre, completed_at,
                     derniere_piste_ia, derniere_piste_at)
    Progression     (score_consolidation, score_projet, sujets_fragiles[])
  Domaine           (slug, label, strategie_key)
```

---

## 4. Couche IA — Architecture

```
Controller
  └── Service métier
        └── AIOrchestrator
              ├── PromptBuilder → DomainPromptStrategy
              ├── IAGatewayInterface → ClaudeAdapter
              ├── AIResponseParser
              └── AIResponseDTOValidator
```

**Règle absolue** : aucun appel Claude API en dehors de ClaudeAdapter.

---

## 5. Symfony Messenger

**Transport** : Doctrine (PostgreSQL) en V1.
**Messages** : IDs uniquement → le handler recharge depuis la base.

**Erreurs temporaires** (timeout, quota) → retry Messenger
**Erreurs fonctionnelles** (JSON invalide, DTO incomplet) → statut ERREUR + UnrecoverableMessageHandlingException

---

## 6. Décisions architecturales figées

| Question | Décision |
|---|---|
| Multilingue | Prévoir FR/EN dès V1 sur les entités |
| Multi-tenant | Non en V1 — utilisateurs individuels uniquement |
| API-first | Twig V1 → API Platform + PWA + Flutter en V2/V3 |
| Cache réponses IA | Base de données uniquement en V1 |
| Soft delete | Oui sur Parcours et User |
| Vérification email | Non en prototype fermé — obligatoire avant ouverture publique |
| Rate limiting login | Oui — login_throttling Symfony dès V1 |

---

## 7. Connexions futures

**V2** : YouTube Data API v3, Symfony Mailer, LanguageTool API, PWA
**V3** : API Platform, Flutter, Stripe, WhatsApp Business API

---

## 8. Organisation des tests

```
tests/
  Unit/
    Service/ConsolidationServiceTest.php
    Service/ParcoursServiceTest.php
    IA/PromptBuilderTest.php
    IA/MockIAGatewayTest.php
  Functional/
    Parcours/CreerParcoursTest.php
    Consolidation/SessionConsolidationTest.php
    Progression/TableauDeBordTest.php
```

---

## Ajustements V1 — Implémentation réelle

### IA : GroqAdapter à la place de ClaudeAdapter

**Décision** : En dev/prod V1, `GroqAdapter` est l'adaptateur actif (Groq API gratuite, modèle `llama-3.3-70b-versatile`). `ClaudeAdapter` reste disponible pour basculer en prod payante.

**Pourquoi c'est correct** : Les deux respectent `IAGatewayInterface`. Le switch se fait en 1 ligne dans `services.yaml` sans toucher aux Services. Format Groq = OpenAI-compatible (standard industrie), plus maintenable à long terme.

```yaml
# Pour basculer sur Claude en prod :
App\Application\IA\Port\IAGatewayInterface:
    class: App\Infrastructure\IA\Gateway\ClaudeAdapter
    arguments:
        $anthropicApiKey: '%env(ANTHROPIC_API_KEY)%'
```

### Multilingue : FR/ES au lieu de FR/EN

Décision produit (public hispanophone). Techniquement identique côté i18n Symfony.

### LocaleSubscriber ajouté

Non prévu dans la spec. Stocke `_locale` en session, lu à chaque requête (priority 20). Nécessaire car les routes n'ont pas de préfixe `{_locale}`.

### Routes : namespace Http obligatoire

```yaml
# config/routes.yaml
controllers:
    resource: ../src/Http/Controller/
    namespace: App\Http\Controller   # DDD — pas le défaut src/Controller/
    type: attribute
```

### Locale par défaut : `fr` (pas `en`)

```yaml
# config/packages/translation.yaml
framework:
    default_locale: fr
```
