# AcquisApp — Roadmap UX : du prototype au produit consommable
**Massagrafik — Juin 2026**

---

## Vision

Transformer AcquisApp d'un prototype fonctionnel (Messenger + Groq + consolidation end-to-end) en un produit vivant, interactif et agréable à utiliser au quotidien. L'objectif est une UX fluide, une UI réactive et une expérience pédagogique cohérente du premier pomodoro jusqu'à la révision espacée.

---

## État de départ (bugs connus)

| Problème | Cause | Gravité |
|---|---|---|
| Barre de progression toujours à 0% | `ProgressionCalculator` non appelé — `RessourceConsolideeEvent` non propagé ou subscriber mal configuré | 🔴 Critique |
| Bouton "Démarrer" sans effet visible | POST → redirect identique, aucun changement UI | 🔴 Critique |
| Écran ressource identique avant/après | Template sans logique d'état | 🔴 Critique |
| Navigation bloquante | Pas de retour arrière, aucun lien de sortie sur certains écrans | 🟠 Majeur |
| Enums comparés en MAJUSCULES dans Twig | `statut.value == 'ACTIF'` faux (valeurs réelles : minuscules) | 🟠 Majeur |
| Réponses IA toujours en français | Langue utilisateur non injectée dans les prompts | 🟠 Majeur |

---

## Sprint 1 — Corrections critiques
**Objectif : rendre l'app utilisable sans friction**

### 1.1 Fix barre de progression (0% → valeur réelle)
- Diagnostiquer `ProgressionSubscriber` + `ProgressionCalculator`
- Vérifier que `RessourceConsolideeEvent` est dispatché dans `ConsolidationService::terminerSession()`
- Vérifier que chaque nouveau `Parcours` crée bien une `Progression` (score initial 0)
- **Fichiers** : `src/Application/Progression/EventSubscriber/ProgressionSubscriber.php`, `src/Application/Progression/Service/ProgressionCalculator.php`

### 1.2 Fix enums Twig (minuscules)
- Remplacer toutes les comparaisons `statut.value == 'ACTIF'` par `statut.value == 'actif'`
- **Fichiers** : `templates/parcours/show.html.twig`, `templates/ressource/show.html.twig`

### 1.3 Écran ressource — 4 états distincts
Actuellement : même affichage quel que soit le statut. Cible :

| État | Ce qu'on affiche |
|---|---|
| **A_FAIRE** | Titre, type, durée, URL · Pomodoros suggérés · CTA "Démarrer" |
| **EN_COURS** | Timer Pomodoro · Pomodoros cliquables · Champ notes · Bouton "Marquer comme vue" |
| **VUE** | Récapitulatif session · CTA "Consolider maintenant" · Lien session en cours si existante |
| **CONSOLIDEE** | Score moyen · Résumé Q&R · Date · Bouton révision planifiée · Lien "Refaire" |

- **Fichiers** : `templates/ressource/show.html.twig` (réécriture), `src/Http/Controller/RessourceController.php` (passer `$sessions` à la vue)

### 1.4 Navigation — toujours un chemin de sortie
Breadcrumb + lien retour sur chaque écran :
- Ressource → `← Retour au parcours`
- Session consolidation → `← Retour à la ressource`
- Projet → `← Retour au parcours`
- Micro-étape → `← Retour au projet`

---

## Sprint 2 — Multilingue systématique dans les réponses IA
**Objectif : l'IA répond dans la langue de l'utilisateur**

### Problème
Tous les prompts envoient du texte en français. L'IA génère donc toujours en français, même si l'utilisateur a choisi l'espagnol. Questions de consolidation, feedbacks, pistes de guidage — tout doit être dans `User.langue`.

### Solution
Injecter une instruction de langue au début de chaque system prompt dans `PromptBuilder` :

```php
$langInstruction = $user->getLangue() === 'es'
    ? "IMPORTANTE: Responde SIEMPRE en español. Todas las preguntas, feedbacks y explicaciones deben estar en español."
    : "IMPORTANT: Réponds TOUJOURS en français. Toutes les questions, feedbacks et explications doivent être en français.";

$system = $langInstruction . "\n\n" . $existingSystemPrompt;
```

Propager `$langue` jusqu'à `AIOrchestrator` (signature de chaque méthode publique) et dans les Handlers Messenger (rechargent `$user` via la chaîne de relations).

**Extensibilité** : ajouter une 3ème langue = 1 fichier `translations/messages.xx.yaml` + 1 clause dans `PromptBuilder`.

**Fichiers** : `src/Application/IA/PromptBuilder.php`, `src/Application/IA/AIOrchestrator.php`, `src/Application/Messenger/Handler/*.php`

---

## Sprint 3 — Session d'apprentissage active
**Objectif : le timer est interactif, les notes sont sauvegardées**

### 3.1 Timer Pomodoro AlpineJS
Remplacer le bouton POST "Démarrer" par un composant client-side :

```
[EN ATTENTE]   → Bouton "🍅 Démarrer (N × 25 min)"
[EN COURS]     → Compte à rebours 25:00 · Pomodoro X/N · Pause · Marquer comme vue
[PAUSE 5 min]  → Compte à rebours 5:00 · Reprendre
[TERMINÉ]      → "Session terminée ✓" · CTA Consolider
```

- État persisté en `localStorage` (résistance au refresh de page)
- Le passage à `EN_COURS` côté serveur reste via le POST existant (déclenché au premier démarrage)

### 3.2 Champ de notes auto-sauvegardé
- Textarea disponible dès que statut = `EN_COURS`
- Auto-save dans `localStorage` toutes les 5 secondes (clé : `notes-ressource-{id}`)
- À l'ouverture du formulaire `TraceApprentissage`, le champ `comprisParUtilisateur` est pré-rempli avec les notes

---

## Sprint 4 — Pomodoros intelligents et adaptatifs
**Objectif : l'expérience s'adapte au contenu (vidéo 2h ≠ article 15min)**

### 4.1 Prompts IA adaptés à la durée
Une vidéo YouTube de 2h unique dans un parcours ne doit pas recevoir le même traitement qu'un cours de 30 min parmi 20 autres.

Injecter dans le prompt consolidation :
- Durée totale de la ressource
- Nombre de pomodoros
- Nombre total de ressources dans le parcours
- Règle IA : `< 30min → questions directes` / `30-90min → questions thématiques` / `> 90min → questions par grande section`

**Fichiers** : `src/Application/IA/PromptBuilder.php`, méthode `buildConsolidationPrompt()`

### 4.2 Pomodoros cliquables avec timestamps vidéo
Pour les ressources `VIDEO` avec URL :

```
[🍅 Pomodoro 1 · 00:00 → 25:00]
  "Introduction et concepts fondamentaux"
  [▶ Ouvrir à ce moment]   [↕ Ajuster]
```

**Calcul** : automatique — `durée_totale / nb_pomodoros` distribué uniformément.

**Ajustement "charmant"** : si les timestamps ne correspondent pas au contenu réel, l'utilisateur clique "↕ Ajuster" → panneau inline :

```
┌──────────────────────────────────────┐
│  Ce découpage ne suit pas le contenu ?│
│  La vraie transition est à [___] min  │
│                      [Enregistrer ✓]  │
└──────────────────────────────────────┘
```

L'ajustement est persisté dans `Ressource.pomodorosCustom` (nouveau champ JSON) via une route dédiée. Les pomodoros suivants sont recalculés en cascade.

**Nouveau champ** :
```php
// src/Domain/Parcours/Entity/Ressource.php
#[ORM\Column(type: 'json', nullable: true)]
private ?array $pomodorosCustom = null;
// Format : [{ 'index': 1, 'debut': 0, 'fin': 1500, 'label': 'Introduction' }, ...]
```

### 4.3 Mode hybride de consolidation
Pour les vidéos longues, deux niveaux de consolidation :

**Niveau 1 — Consolidation par segment** (optionnel)
- Bouton "📝 Consolider ce segment" dans chaque carte pomodoro
- Mini-session : 3 questions contextuelles à CE segment uniquement
- Stockée comme `TypeSession::INITIALE` avec contexte du segment dans le prompt

**Niveau 2 — Consolidation globale** (toujours disponible)
- Bouton "✅ Consolider la ressource complète" à la fin de tous les pomodoros
- Session globale : questions de synthèse sur toute la ressource
- Si des mini-sessions existent déjà → type `TypeSession::REVISION`, prompt enrichi des segments déjà consolidés

---

## Sprint 5 — Vocabulaire et communication
**Objectif : chaque mot renforce l'intention pédagogique**

### 5.1 Vocabulaire multilingue ajusté

| Terme actuel | FR (garder) | ES (changer) |
|---|---|---|
| Parcours | **Parcours** ✓ | **Ruta de aprendizaje** (LinkedIn Learning, Google) |
| Recorrido | — | Remplacé par "Ruta" |
| Statut badges | Voir ci-dessous | Traduire en conséquence |

**Fichiers** : `translations/messages.es.yaml`

### 5.2 Statuts contextuels et parlants

| Statut technique | Affichage FR | Affichage ES |
|---|---|---|
| `a_faire` | "Pas encore commencé" | "Aún no iniciado" |
| `en_cours` | "En cours · X 🍅" | "En progreso · X 🍅" |
| `vue` | "Vu · Prêt à consolider" | "Visto · Listo para consolidar" |
| `consolidee` | "Consolidée ✓ · X/5" | "Consolidado ✓ · X/5" |

---

## Sprint 6 — UI interactive et vivante
**Objectif : éliminer les rechargements de page, ajouter des animations**

### 6.1 AlpineJS polling pour la consolidation
Remplacer le `<meta http-equiv="refresh" content="5">` par un polling propre :

Nouvel endpoint JSON :
```
GET /consolidation/session/{id}/statut
→ { "statut": "pret", "redirect": "/consolidation/session/{id}" }
```

Composant AlpineJS qui interroge cet endpoint toutes les 3s et redirige automatiquement quand `statut === 'pret'`.

### 6.2 Skeleton loading
Pendant `EN_ATTENTE` / `GENERATION` : afficher des blocs gris animés à la place des futurs contenus (questions, exercice) plutôt qu'un message texte.

### 6.3 Animations et transitions
- Progress bar animée (transition CSS) sur le parcours
- Fade-in des questions à leur apparition
- Badge statut avec transition de couleur
- Indicateur "génération IA" avec animation pulse

---

## Calendrier indicatif

| Semaine | Sprint | Livrable | Statut |
|---|---|---|---|
| 1 | Sprint 1 | App utilisable sans blocage — progression visible, navigation fluide | ✅ Livré 2026-06-30 |
| 1 | Sprint 1.5 | Dashboard 4 zones, sujets abordés, projet cliquable, progression fix | ✅ Livré 2026-06-30 |
| 2 | Sprint 2 | L'IA répond en FR ou ES selon l'utilisateur | ⏳ À venir |
| 3 | Sprint 3 | Timer interactif, prise de notes, session vivante | ⏳ À venir |
| 4 | Sprint 4 | Pomodoros cliquables, ajustement timestamps, mode hybride consolidation | ⏳ À venir |
| 5 | Sprints 5+6 | Vocabulaire affiné, polling AlpineJS, animations | ⏳ À venir |

---

## Backlog idées — V2/V3

*Non priorisé — à évaluer selon retours utilisateurs*

### Pédagogie
- [ ] Évaluation surprise aléatoire sur ressources consolidées > 14j
- [ ] Score de "rétention" mesuré aux révisions (distinct du score consolidation)
- [ ] Affichage de la ressource suivante suggérée par l'IA (déjà dans le DTO)
- [ ] Mode "révision flash" : 5 questions rapides issues de l'historique

### UX / UI
- [ ] Onboarding guidé au premier parcours (tooltip tour)
- [ ] Célébration visuelle (confetti) à la première consolidation et fin de parcours
- [ ] Raccourcis clavier : espace = pause timer, N = focus notes
- [ ] PWA : service worker, install prompt (Coolify-compatible)
- [ ] Mode clair/sombre toggle

### IA
- [ ] Basculer sur Claude (Anthropic) en production pour qualité supérieure
- [ ] Prompt différent selon `ModeAccompagnement` sur les questions (pas seulement le guidage)
- [ ] Détection automatique durée YouTube via YouTube Data API v3
- [ ] Résumé de ressource en 3 bullets généré par l'IA

### Technique
- [ ] Tests fonctionnels Symfony (parcours + consolidation end-to-end)
- [ ] CI/CD GitHub Actions → Coolify auto-deploy
- [ ] API Platform pour clients mobiles (V3)
- [ ] LanguageTool API pour feedback orthographique sur rendus écrits

---

## Décisions produit actées

| Sujet | Décision |
|---|---|
| IA adaptateur | GroqAdapter actif en dev/prod V1 — basculer sur Claude en prod V2 |
| Langues | FR + ES uniquement — "Ruta de aprendizaje" en ES |
| Timestamps pomodoros | Calcul automatique + ajustement utilisateur persisté |
| Consolidation vidéos longues | Mode hybride : segment (optionnel) + global (toujours disponible) |
| Polling consolidation | meta-refresh 5s en V1 → AlpineJS Sprint 6 |
| Worker Messenger | Manuel en dev : `php bin/console messenger:consume async` |
