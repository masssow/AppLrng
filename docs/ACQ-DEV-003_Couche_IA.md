# ACQ-DEV-003 — Couche IA et PromptBuilder
**Massagrafik — Juin 2026**

---

## 1. Décisions figées

- **D1** : Prompts hardcodés en PHP en V1, versionnés via PromptVersions. Jamais de modification silencieuse.
- **D2** : Cache réponses IA = base de données uniquement. Pas de Redis en V1.
- **D3** : Pas d'entité GuidageIA. Stockage sur MicroEtape.dernierePisteIa + dernierePisteAt.
- **D4** : Aucune réponse IA brute ne crée directement des entités Doctrine.
- **D5** : ClaudeAdapter expose uniquement `complete(PromptDTO): RawAIResponse`. La logique métier appartient à AIOrchestrator.
- **D6** : StatutSession inclut ERREUR.

---

## 2. Architecture en couches

```
Controller
  └── Service métier
        └── AIOrchestrator
              ├── PromptBuilder → DomainPromptStrategy
              ├── IAGatewayInterface → ClaudeAdapter
              ├── AIResponseParser
              └── AIResponseDTOValidator
```

### Flux complet

```
Service métier
→ AIOrchestrator::genererConsolidation(trace, domaine, niveau)
     → PromptBuilder::buildConsolidationPrompt() → PromptDTO
     → IAGatewayInterface::complete(PromptDTO) → RawAIResponse
     → AIResponseParser::parse(RawAIResponse) → array
     → AIResponseDTOValidator::validate(array) → ConsolidationDTO
→ Service métier persiste le DTO en entités Doctrine
```

---

## 3. Composants

### AIOrchestrator
```php
// App\Application\IA\AIOrchestrator
public function structurerParcours(string $objectif, NiveauMaitrise $niveau, string $domaine, array $titresRessources): ParcoursStructureDTO;
public function genererConsolidation(TraceApprentissage $trace, string $domaine, NiveauMaitrise $niveau): ConsolidationDTO;
public function evaluerReponse(string $questionOuEnonce, string $reponseUtilisateur, string $domaine, NiveauMaitrise $niveau): EvaluationDTO;
public function guiderMicroEtape(MicroEtape $etape, string $blocageDecrit, string $contexteProjet, array $historiqueConsolide, string $modeAccompagnement): string;
```

### IAGatewayInterface (Port applicatif)
```php
// App\Application\IA\Port\IAGatewayInterface
public function complete(PromptDTO $prompt): RawAIResponse;

// Implémentations :
// Infrastructure\IA\Gateway\ClaudeAdapter    → production (payant)
// Infrastructure\IA\Gateway\GroqAdapter      → dev/prod V1 (gratuit)
// Infrastructure\IA\Gateway\MockIAGateway    → tests + dev sans clé
```

### PromptBuilder
```php
// App\Application\IA\PromptBuilder
public function buildParcoursPrompt(string $objectif, NiveauMaitrise $niveau, string $domaine, array $ressources): PromptDTO;
public function buildConsolidationPrompt(TraceApprentissage $trace, string $domaine, NiveauMaitrise $niveau, string $version): PromptDTO;
public function buildEvaluationPrompt(string $questionOuEnonce, string $reponseUtilisateur, string $domaine, NiveauMaitrise $niveau): PromptDTO;
public function buildGuidagePrompt(MicroEtape $etape, string $blocageDecrit, string $contexteProjet, array $historiqueConsolide, string $modeAccompagnement): PromptDTO;
```

### PromptDTO
```
type            : PromptType (enum)
version         : string
system          : string
user            : string
expected_format : string  (json | text)
temperature     : float
metadata        : array
```

### DomainPromptStrategyInterface
```php
// App\Application\IA\Strategy\DomainPromptStrategyInterface
public function supportsDomaine(string $key): bool;
public function getSystemContext(): string;
public function getConsolidationGuidelines(): string;
public function getEvaluationGuidelines(): string;
public function getExerciseGuidelines(): string;
public function getSuggestedTools(): array;
```

Implémentations : DevWebStrategy, DesignStrategy, LangueStrategy, ComptabiliteStrategy, DefaultStrategy

| Strategy | domaine_key | Spécificité |
|---|---|---|
| DevWebStrategy | dev-web | Exercices = code, outil = CodeSandbox/Replit |
| DesignStrategy | design | Exercices = brief créatif, outil = Figma/Canva |
| LangueStrategy | langue | Exercices = production écrite, LanguageTool |
| ComptabiliteStrategy | comptabilite | Exercices = cas chiffré, Google Sheets |
| DefaultStrategy | autre | Générique |

### AIResponseParser
```php
// App\Infrastructure\IA\Parser\AIResponseParser
public function parseJSON(RawAIResponse $raw): array;
public function parseText(RawAIResponse $raw): string;
// Supprime les backticks ```json, extrait le JSON, lève ParseException si invalide
```

### AIResponseDTOValidator
```php
// App\Application\IA\Validator\AIResponseDTOValidator
public function validate(array $data, string $dtoClass): object;
// Champs requis absents → InvalidDTOException
// Champs optionnels manquants → valeur par défaut
// Score EvaluationDTO clampé : max(1, min(5, score))
```

---

## 4. Versioning des prompts

```php
enum PromptType: string {
    case PARCOURS      = 'parcours';
    case CONSOLIDATION = 'consolidation';
    case EVALUATION    = 'evaluation';
    case GUIDAGE       = 'guidage';
}

class PromptVersions {
    const PARCOURS_V1             = 'parcours_v1.0';
    const CONSOLIDATION_V1        = 'consolidation_v1.0';
    const EVALUATION_V1           = 'evaluation_v1.0';
    const GUIDAGE_V1              = 'guidage_v1.0';
    const GUIDAGE_SOCRATIQUE_V1   = 'guidage_socratique_v1.0';
    const GUIDAGE_MIXTE_V1        = 'guidage_mixte_v1.0';
    const GUIDAGE_EXPLICATIF_V1   = 'guidage_explicatif_v1.0';
}
```

---

## 5. Prompts — Structure par type

### Type 1 — Structuration de parcours
```
SYSTEM : Architecte pédagogique expert en [domaine]. Réponds en JSON valide uniquement.

USER :
Objectif : [objectif] | Niveau : [niveau] | Domaine : [domaine]
Ressources : r1: [titre_1] | r2: [titre_2] | ...

JSON attendu :
{
  "themes": [{ "titre": "", "ressources_refs": ["r1", "r2"] }],
  "ordre_suggere": ["r1", "r3", "r2"],
  "prerequis_detectes": [],
  "risques_apprentissage": [],
  "projet_fil_rouge": {
    "titre": "", "description": "",
    "micro_etapes": [{ "titre": "", "type": "", "description": "" }]
  },
  "suggestion_ressource_manquante": "" ou null
}
```

### Type 2 — Génération de consolidation
```
SYSTEM : Tuteur pédagogique expert en [domaine]. [system_context Strategy]
Questions de compréhension active, pas de mémorisation. JSON uniquement.

USER :
Ressource : [titre] | Compris : [compris] | Points flous : [flous]
Application : [application] | Niveau : [niveau] | Confiance : [N]/5
[consolidation_guidelines Strategy]

JSON attendu :
{
  "questions": [
    { "texte": "", "type": "comprehension|application|analyse" },
    { "texte": "", "type": "" },
    { "texte": "", "type": "" }
  ],
  "exercice": {
    "enonce": "",
    "outil_suggere": { "nom": "", "url": "", "instructions": "" } ou null,
    "criteres_reussite": []
  },
  "niveau_difficulte": "facile|moyen|difficile",
  "concepts_cibles": [],
  "ressource_suivante_suggeree": "" ou null
}
```

### Type 3 — Évaluation pédagogique
```
SYSTEM : Tuteur bienveillant mais honnête. Feedback actionnable. JSON uniquement.
[evaluation_guidelines Strategy]

USER :
Question/Énoncé : [texte] | Réponse/Rendu : [reponse] | Domaine : [domaine] | Niveau : [niveau]

JSON attendu :
{
  "score": 1-5,
  "decision": "A_REVOIR|PARTIEL|VALIDE",
  "feedback": "",
  "point_fort": "",
  "point_amelioration": "",
  "encouragement": ""
}
```
Règle : A_REVOIR = score 1-2, PARTIEL = 3, VALIDE = 4-5.

### Type 4 — Guidage de micro-étape (3 modes)
```
// SOCRATIQUE (défaut) :
SYSTEM : Tu es un mentor Socratique. Tu utilises l'historique du parcours.
Tu poses UNE question qui amène l'apprenant à trouver lui-même. Max 3 phrases. Texte simple.

// MIXTE :
SYSTEM : Tu es un tuteur bienveillant. Tu poses une question de guidage + un indice progressif.
Tu t'appuies sur les concepts consolidés. Max 4 phrases. Texte simple.

// EXPLICATIF :
SYSTEM : Tu es un formateur clair et direct. Tu expliques le concept bloqué simplement.
Tu relies aux acquis du parcours. Max 5 phrases. Texte simple.

USER :
Contexte projet : [description] | Étape : [titre] — [description]
Blocage : [blocage] | Domaine : [domaine]
Mode : [SOCRATIQUE|MIXTE|EXPLICATIF]

Historique consolidé :
  - [titre_ressource] : [concepts_cibles] ([N] Pomodoros)
```

---

## 6. DTOs de retour

```
ParcoursStructureDTO
├── themes: ThemeDTO[]
├── ordreSuggere: string[]
├── prerequisDetectes: string[]
├── risquesApprentissage: string[]
├── projetFilRouge: ProjetDTO
└── suggestionRessourceManquante: string?

ConsolidationDTO
├── questions: QuestionDTO[]  (3 éléments)
├── exercice: ExerciceDTO
├── niveauDifficulte: string
├── conceptsCibles: string[]
└── ressourceSuivanteSuggeree: string?

EvaluationDTO
├── score: int  (1-5)
├── decision: string  (A_REVOIR|PARTIEL|VALIDE)
├── feedback: string
├── pointFort: string
├── pointAmelioration: string
└── encouragement: string

QuestionDTO : texte, type
ExerciceDTO : enonce, outilSuggere (OutilDTO?), criteresReussite[]
OutilDTO    : nom, url, instructions
```

---

## 7. Gestion des erreurs IA

| Famille | Exemples | Action |
|---|---|---|
| Temporaire | Timeout, API down, quota | Laisser remonter → Messenger retry |
| Définitive | JSON invalide, DTO incomplet | Statut ERREUR + UnrecoverableMessageHandlingException |

```php
} catch (AIGenerationException $e) {
    throw $e; // retry Messenger
} catch (ParseException|InvalidDTOException $e) {
    $session->setStatut(StatutSession::ERREUR);
    $session->setGenerationError($e->getMessage());
    $this->em->flush();
    throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
}
```

---

## 8. MockIAGateway

```php
// App\Infrastructure\IA\Gateway\MockIAGateway
// implements IAGatewayInterface
// Zéro appel API réel — utilisé dans les tests ET en dev sans clé API
// Retourne des réponses JSON valides selon le PromptType
```

---

## Ajustements V1 — Implémentation réelle

### Adaptateur actif : GroqAdapter (pas ClaudeAdapter)

**Pourquoi** : API Groq gratuite pour le dev. L'interface `IAGatewayInterface` garantit le swap sans impact sur les Services.

```php
// src/Infrastructure/IA/Gateway/GroqAdapter.php
private const MODEL   = 'llama-3.3-70b-versatile';
private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

// Format requête (OpenAI-compatible) :
'messages' => [
    ['role' => 'system', 'content' => $prompt->system],
    ['role' => 'user',   'content' => $prompt->user],
]

// Format réponse (différent de Claude) :
$data['choices'][0]['message']['content']   // contenu
$data['usage']['prompt_tokens']             // input
$data['usage']['completion_tokens']         // output
```

### services.yaml — double déclaration obligatoire

```yaml
# L'autowiring ne propage pas les arguments entre alias et classe concrète
App\Infrastructure\IA\Gateway\GroqAdapter:
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'

App\Application\IA\Port\IAGatewayInterface:
    class: App\Infrastructure\IA\Gateway\GroqAdapter
    arguments:
        $groqApiKey: '%env(GROQ_API_KEY)%'   # OBLIGATOIRE — répéter ici aussi
```

### QuestionDTO.type — non persisté dans l'entité

`QuestionDTO` a un champ `type` (string: 'comprehension'|'application'|'analyse') mais l'entité `Question` n'a pas ce champ. À la persistance, utiliser uniquement `$dto->texte` et l'index de boucle comme `$ordre`.

### ExerciceDTO — propriétés exactes

```php
// ExerciceDTO
public string $enonce
public ?OutilDTO $outilSuggere    // PAS $outil
public array $criteresReussite    // présent dans DTO, mais Exercice n'a pas setCriteresReussite()
```

### StrategyResolver — DefaultStrategy exclue du tagged iterator

```yaml
App\Application\IA\Strategy\StrategyResolver:
    arguments:
        $strategies: !tagged_iterator { tag: 'app.ia.strategy', exclude: 'App\Application\IA\Strategy\DefaultStrategy' }
```
`DefaultStrategy` est injectée séparément comme fallback.
