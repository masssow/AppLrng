# ACQ-DEV-006 — Flows Utilisateur
**Massagrafik — Juin 2026**

---

## Flow 1 — Inscription et onboarding

```
1. GET /inscription
   → Formulaire : email, mot de passe, prénom, nom
2. POST /inscription
   → Création User (NiveauMaitrise::DEBUTANT, ModeAccompagnement::SOCRATIQUE, langue='fr')
   → Flash success + redirect dashboard
3. GET /tableau-de-bord
   → Si 0 parcours : CTA "Créer votre premier parcours"
   → Si parcours : résumé activité
```

**État initial** : User sans parcours, dashboard vide avec message de bienvenue.

---

## Flow 2 — Création d'un parcours

```
1. GET /parcours/nouveau
   → Formulaire : titre, objectif, domaine (select Domaine), niveau (select NiveauMaitrise), durée cible (semaines)
   → Saisie des ressources : titre, type (TypeRessource), URL optionnelle

2. POST /parcours/nouveau
   → ParcoursService::creer(data, user)
     → Parcours statut BROUILLON
     → Ressources créées (statut: A_FAIRE, ordre: séquentiel)
     → dispatch GenerationParcoursMessage(parcoursId)
   → Redirect /parcours/{id}

3. GET /parcours/{id} [statut: BROUILLON]
   → Indicateur "Structuration IA en cours..."
   → Auto-refresh 5s

4. Worker [GenerationParcoursHandler]
   → AIOrchestrator::structurerParcours()
   → Réordonne Ressources selon ordre_suggere
   → Crée ProjetFilRouge + MicroEtapes (1ère DISPONIBLE, reste VERROUILLEE)
   → Parcours statut → ACTIF
   → dispatch ParcoursStructureEvent

5. GET /parcours/{id} [statut: ACTIF]
   → Liste ressources ordonnées
   → ProjetFilRouge + liste MicroEtapes
   → Progression initialisée (score 0)
```

---

## Flow 3 — Session Pomodoro (consommation ressource)

```
1. GET /ressource/{id}
   → Détail ressource : titre, type, URL, description, durée estimée
   → pomodorosSuggeres affiché comme objectif
   → Bouton "Marquer comme vue" si statut A_FAIRE ou EN_COURS
   → Timer Pomodoro (25/5 min)
   → Si statut VUE ou CONSOLIDEE : bouton "Consolider"

2. POST /ressource/{id}/vue
   → Ressource statut → VUE, viewedAt = now
   → Redirect /ressource/{id}

3. Timer Pomodoro (côté client, AlpineJS en spec)
   → Compte à rebours 25 min
   → Alerte pause 5 min
   → Compteur cycles
   → Pas de persistance serveur — informationnel
```

---

## Flow 4 — Consolidation (cœur pédagogique)

```
1. GET /ressource/{id}/consolider
   → Formulaire TraceApprentissage :
     - comprisParUtilisateur (textarea)
     - pointsFlous (textarea)
     - applicationPossible (textarea)
     - confianceUtilisateur (1-5, select)
     - pomodorosEffectues (integer)

2. POST /ressource/{id}/consolider
   → ConsolidationService::initier(trace_data, ressource, user)
     → crée TraceApprentissage
     → crée SessionConsolidation (statut: EN_ATTENTE)
     → dispatch GenerationConsolidationMessage(sessionId, traceId)
   → Flash "Génération en cours..."
   → Redirect /consolidation/session/{sessionId}

3. GET /consolidation/session/{id} [EN_ATTENTE ou GENERATION]
   → Message "Génération IA en cours..."
   → Auto-refresh 5s

4. Worker [GenerationConsolidationHandler]
   → SessionConsolidation statut → GENERATION
   → AIOrchestrator::genererConsolidation(trace, domaine, niveau)
   → Persiste 3 Questions + 1 Exercice
   → SessionConsolidation statut → PRET
   → dispatch ConsolidationPreteEvent

5. GET /consolidation/session/{id} [PRET]
   → Affiche les 3 Questions (une par une ou toutes)
   → Formulaire réponse par question
   → Affiche l'Exercice

6. POST /consolidation/session/{id} [réponse question]
   → ConsolidationService::soumettreReponseQuestion(question, reponse, user)
     → Question.reponseUtilisateur persistée
     → Question.statutEvaluation = EVALUATION
     → dispatch EvaluationReponseMessage(TYPE_QUESTION, questionId)
   → Redirect /consolidation/session/{id}

7. POST /consolidation/session/{id} [rendu exercice]
   → ConsolidationService::soumettreRenduExercice(exercice, rendu, user)
     → Exercice.renduUtilisateur persisté
     → Exercice.statutEvaluation = EVALUATION
     → dispatch EvaluationReponseMessage(TYPE_EXERCICE, exerciceId)
   → Redirect /consolidation/session/{id}

8. Worker [EvaluationReponseHandler]
   → AIOrchestrator::evaluerReponse()
   → Question/Exercice.feedbackIa + feedbackScore + decision
   → statutEvaluation → EVALUEE

9. SessionConsolidation statut → COMPLETE quand toutes questions + exercice EVALUEE
   → dispatch RessourceConsolideeEvent
   → ProgressionSubscriber calcule nouveau score
   → RevisionSubscriber crée RevisionSpacee(iter=1, datePrevue=J+1)
```

---

## Flow 5 — Projet fil rouge (micro-étapes)

```
1. GET /parcours/{id}/projet
   → ProjetFilRouge : titre, description
   → Liste MicroEtapes ordonnées avec statuts (icônes verrouillée/disponible/en cours/complète)

2. GET /projet/{projetId}/etape/{etapeId}
   → Détail MicroEtape : titre, description, type, outil externe suggéré
   → Zone rendu (textarea) si statut DISPONIBLE ou EN_COURS
   → Bouton "Besoin d'une piste" (disponible si DISPONIBLE ou EN_COURS)
   → Zone feedback IA avec dernierePisteIa (si déjà une piste)

3. POST /projet/{projetId}/etape/{etapeId}/piste
   → MicroEtapeService::demanderPiste(etape, blocage, user)
     → AIOrchestrator::guiderMicroEtape(etape, blocage, modeAccompagnement user)
     → MicroEtape.dernierePisteIa = réponse, dernierePisteAt = now
   → Redirect /projet/{projetId}/etape/{etapeId}

4. POST /projet/{projetId}/etape/{etapeId}/complete
   → MicroEtapeService::terminer(etape, rendu, user)
     → Etape.renduUtilisateur = rendu
     → Etape statut → COMPLETE
     → Etape suivante : VERROUILLEE → DISPONIBLE
   → Si toutes complètes : ProjetFilRouge statut → TERMINE → dispatch ProjetTermineEvent
   → Redirect /parcours/{id}/projet
```

---

## Flow 6 — Révisions espacées

```
1. GET /tableau-de-bord
   → Section "Révisions du jour" : liste RevisionSpacee où datePrevue <= today AND completeeAt IS NULL

2. GET /revision/{id}
   → Rappel de la ressource (titre, URL)
   → Auto-évaluation 1-5 (sans IA en V1 — juste le rappel)
   → Bouton "J'ai révisé"

3. POST /revision/{id}/complete
   → RevisionService::terminer(revision, score, user)
     → revision.completeeAt = now, revision.score = score
     → Si score >= 3 et iteration < 5 : crée RevisionSpacee suivante (iter+1, intervalle suivant)
     → Si score < 3 : crée RevisionSpacee avec intervalle réduit (max(J+1, intervalle/2))
     → Si iteration >= 5 : dernière révision, pas de nouvelle
   → Redirect /tableau-de-bord
```

---

## États et transitions

### Ressource
```
A_FAIRE → (marquer vue) → VUE → (consolidation COMPLETE) → CONSOLIDEE
A_FAIRE → (démarrage) → EN_COURS → VUE → CONSOLIDEE
```

### SessionConsolidation
```
EN_ATTENTE → (worker start) → GENERATION → (IA OK) → PRET → (user termine) → COMPLETE
*          → (IA fail x3) → ERREUR
```

### MicroEtape
```
VERROUILLEE → (précédente COMPLETE) → DISPONIBLE → (user démarre) → EN_COURS → (user termine) → COMPLETE
```

### RevisionSpacee — Intervalles
```
iter 1 → J+1
iter 2 → J+3
iter 3 → J+7
iter 4 → J+14
iter 5 → J+30
Score < 3 → intervalle = max(1j, intervalle/2)
```

---

## Navigation et UI

### Structure globale
```
/tableau-de-bord          → Hub central
/parcours/                → Mes parcours
/parcours/{id}            → Détail parcours + ressources + projet
/ressource/{id}           → Détail ressource (timer, statut, bouton consolider)
/ressource/{id}/consolider → Formulaire TraceApprentissage
/consolidation/session/{id} → Session en cours / résultats
/parcours/{id}/projet     → Vue projet fil rouge
/projet/{id}/etape/{id}   → Détail micro-étape
/revision/{id}            → Révision espacée
```

### Breadcrumbs obligatoires
Chaque vue affiche le fil d'Ariane pour navigation inverse :
- Ressource → Parcours
- Session consolidation → Ressource → Parcours
- Micro-étape → Projet → Parcours

---

## Ajustements V1 — Implémentation réelle

### Polling — meta-refresh (pas AlpineJS)

La spec prévoyait :
- AlpineJS + endpoint JSON `/consolidation/statut/{id}` (polling toutes les 2s)
- Timer Pomodoro AlpineJS (Flow 3)

Implémenté en V1 :
```twig
{# consolidation/show.html.twig — statut en_attente ou generation #}
<meta http-equiv="refresh" content="5">
```
Rechargement page complète toutes les 5s. Timer Pomodoro = non implémenté en V1.
Upgrade AlpineJS prévu en V2.

### Flow 4 — Route unique POST (pas routes séparées)

Spec prévoyait : `/question/{id}/reponse` et `/exercice/{id}/rendu`

Implémenté : POST sur `app_consolidation_show` avec champs cachés :
```html
<input type="hidden" name="question_id" value="{{ question.id }}">
<!-- OU -->
<input type="hidden" name="exercice_id" value="{{ session.exercice.id }}">
```
Le Controller `show()` détecte `question_id` vs `exercice_id` dans la requête POST.

### Enums — valeurs minuscules dans Twig

```twig
{# CORRECT #}
{% if session.statut.value == 'en_attente' %}
{% if session.statut.value == 'pret' %}

{# FAUX — enum values sont minuscules #}
{% if session.statut.value == 'EN_ATTENTE' %}
```

### Syntaxe commentaires Twig

```twig
{# Commentaire Twig — CORRECT #}
{{# Commentaire Handlebars — FAUX, provoque SyntaxError #}}
```

### Worker — lancement manuel en dev

Aucun Supervisor en dev local. Worker à lancer manuellement dans un terminal dédié :
```bash
php bin/console messenger:consume async -vv
# À garder ouvert pendant toute la session de dev
```

### Inscription — langue détectée par LocaleSubscriber

À l'inscription, la langue de l'utilisateur est déterminée par `LocaleSubscriber` (stocke `_locale` en session). Le champ `User.langue` est initialisé à `'fr'` (défaut) dans le Controller.
