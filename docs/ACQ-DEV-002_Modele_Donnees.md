# ACQ-DEV-002 — Modèle de données applicatif
**Massagrafik — Juin 2026**

---

## 1. Décisions fondamentales

- **D1** : TraceApprentissage est une entité dédiée capturant la perception utilisateur après consommation d'une ressource.
- **D2** : Une Ressource peut avoir plusieurs SessionConsolidation (reprise, révision, régénération).
- **D3** : Une SessionConsolidation peut référencer une parent_session pour tracer les régénérations.
- **D4** : La Progression est un snapshot calculé, stocké pour performance, toujours recalculable par ProgressionCalculator.
- **D5** : Les feedbacks IA sont stockés. Une régénération crée une nouvelle SessionConsolidation — jamais de modification silencieuse.
- **D6** : Domaine.strategie_key est une clé métier stable. La résolution vers une Strategy se fait côté code.
- **D7** : Scores pédagogiques sur échelle 1 à 5. Champs de Progression = snapshots calculés uniquement.

---

## 2. Vue d'ensemble

```
User
  Parcours             (objectif, duree, domaine, statut, derniere_eval_surprise_at)
    Ressource          (titre, type, url, ordre, statut, viewed_at, consolidated_at,
                        duree_estimee_minutes, pomodoros_suggeres)
      TraceApprentissage  (compris, points_flous, confiance 1-5, pomodoros_effectues)
      SessionConsolidation  (statut, prompt_version, parent_session?)
        Question       (texte, reponse_user, feedback_ia, decision, statut_evaluation)
        Exercice       (enonce, outil_suggere, rendu_user, feedback_ia, statut_evaluation)
      RevisionSpacee   (iteration, date_prevue, score, completee_at)
    ProjetFilRouge     (titre, description, statut)
      MicroEtape       (titre, type, outil_externe?, statut, ordre, completed_at,
                        derniere_piste_ia, derniere_piste_at)
    Progression        (score_consolidation, score_projet, sujets_fragiles[])
  Domaine              (slug, label, strategie_key)
```

---

## 3. Entités — Définition complète

### User
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | uuid v4 |
| email | string | Unique, index |
| password | string | Haché |
| prenom | string | |
| nom | string | |
| niveau | string (enum) | NiveauMaitrise |
| plan | string (enum) | PlanUtilisateur — GRATUIT\|PREMIUM (V2) |
| modeAccompagnement | string (enum) | ModeAccompagnement — défaut SOCRATIQUE |
| langue | string | fr\|es — défaut fr |
| avatarUrl | string? | Nullable — pas d'entité Avatar en V1 |
| createdAt | datetime_immutable | |
| updatedAt | datetime | |
| deletedAt | datetime? | Soft delete |

### Domaine
| Champ | Type Doctrine | Note |
|---|---|---|
| id | integer | Auto-increment |
| slug | string | dev-web\|design\|langue\|comptabilite\|autre — unique |
| label | string | Libellé affiché |
| icone | string | Nom icône |
| strategieKey | string | Clé métier — Strategy côté code |

Peuplée en fixtures uniquement. Pas de CRUD utilisateur.

### Parcours
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| user | ManyToOne → User | Non nullable |
| domaine | ManyToOne → Domaine | Non nullable |
| titre | string | |
| objectif | text | |
| niveau | string (enum) | NiveauMaitrise |
| dureeCibleSemaines | integer | |
| statut | string (enum) | StatutParcours |
| derniereEvalSurpriseAt | datetime? | Anti-double évaluation surprise |
| createdAt | datetime_immutable | |
| updatedAt | datetime | |
| deletedAt | datetime? | Soft delete |

### Ressource
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| parcours | ManyToOne → Parcours | Non nullable |
| titre | string | |
| type | string (enum) | TypeRessource |
| url | string? | Nullable |
| source | string? | youtube\|udemy\|manuel\|autre |
| description | text? | |
| ordre | integer | |
| statut | string (enum) | StatutRessource |
| dureeEstimeeMinutes | integer? | Base du calcul Pomodoro |
| pomodorosSuggeres | integer? | dureeEstimeeMinutes/25, plafond 6 |
| viewedAt | datetime? | Timestamp passage à VUE |
| consolidatedAt | datetime? | Timestamp première consolidation |
| createdAt | datetime_immutable | |
| updatedAt | datetime | |

### TraceApprentissage
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| ressource | ManyToOne → Ressource | Non nullable |
| user | ManyToOne → User | Redondant mais utile |
| comprisParUtilisateur | text? | |
| pointsFlous | text? | |
| applicationPossible | text? | |
| confianceUtilisateur | integer? | 1 à 5 |
| pomodorosEffectues | integer? | Cycles Pomodoro déclarés |
| createdAt | datetime_immutable | |
| updatedAt | datetime | |

Règle : `user` = propriétaire du Parcours lié — vérifié dans ConsolidationService.

### SessionConsolidation
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| ressource | ManyToOne → Ressource | Non nullable |
| traceApprentissage | ManyToOne → TraceApprentissage? | Nullable |
| parentSession | ManyToOne → self? | Self-reference — trace régénérations |
| type | string (enum) | TypeSession |
| statut | string (enum) | StatutSession |
| promptVersion | string | Ex: consolidation_v1.0 |
| modeleIa | string? | Ex: claude-sonnet-4-6 |
| reponseIaBrute | json? | Stockée pour audit/debug |
| generationError | text? | |
| regenerationReason | text? | |
| generatedAt | datetime? | Fin de génération IA |
| createdAt | datetime_immutable | |
| completedAt | datetime? | Completion par l'utilisateur |

Règle : régénération = nouvelle entité, jamais modification silencieuse.

### Question
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| session | ManyToOne → SessionConsolidation | Non nullable |
| texte | text | Généré par l'IA |
| reponseUtilisateur | text? | |
| feedbackIa | text? | |
| feedbackScore | integer? | 1 à 5 |
| decision | string? | A_REVOIR\|PARTIEL\|VALIDE |
| validee | boolean | Défaut false |
| statutEvaluation | string (enum) | StatutEvaluation |
| ordre | integer | 1, 2 ou 3 — 3 questions/session en V1 |

### Exercice
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| session | OneToOne → SessionConsolidation | Non nullable |
| enonce | text | Généré par l'IA |
| outilSuggere | json? | { nom, url, instructions } ou null |
| renduUtilisateur | text? | |
| feedbackIa | text? | |
| feedbackScore | integer? | 1 à 5 |
| decision | string? | A_REVOIR\|PARTIEL\|VALIDE |
| statutEvaluation | string (enum) | StatutEvaluation |

### RevisionSpacee
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| ressource | ManyToOne → Ressource | Non nullable |
| user | ManyToOne → User | Redondant mais utile |
| iteration | integer | 1→5 — détermine l'intervalle suivant |
| datePrevue | date | J+1\|J+3\|J+7\|J+14\|J+30 |
| completeeAt | datetime? | |
| score | integer? | Auto-évaluation 1 à 5 |
| reporteAt | datetime? | |

Intervalles : iter1=J+1, 2=J+3, 3=J+7, 4=J+14, 5=J+30. Score<3 → intervalle/2 min J+1.

### ProjetFilRouge
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| parcours | OneToOne → Parcours | Non nullable |
| titre | string | |
| description | text | |
| statut | string (enum) | StatutProjet |
| promptVersion | string | |
| reponseIaBrute | json? | Structure générée par l'IA |
| createdAt | datetime_immutable | |

### MicroEtape
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| projet | ManyToOne → ProjetFilRouge | Non nullable |
| titre | string | |
| description | text | |
| type | string (enum) | TypeMicroEtape |
| outilExterne | json? | { nom, url, template_url?, instructions } |
| renduUtilisateur | text? | |
| feedbackIa | text? | |
| statut | string (enum) | StatutEtape |
| ordre | integer | |
| debloqueeAt | datetime? | Timestamp de déblocage |
| completedAt | datetime? | Timestamp de completion |
| dernierePisteIa | text? | Dernière piste de guidage IA |
| dernierePisteAt | datetime? | Timestamp dernière piste |

Règle déblocage : MicroEtape passe VERROUILLEE→DISPONIBLE quand la précédente est COMPLETE. Géré par MicroEtapeService.

### Progression
| Champ | Type Doctrine | Note |
|---|---|---|
| id | Uuid | |
| parcours | OneToOne → Parcours | Non nullable |
| scoreConsolidation | integer | 0 à 100 — calculé |
| scoreProjet | integer | 0 à 100 — calculé |
| ressourcesTotal | integer | Snapshot |
| ressourcesConsolidees | integer | Snapshot |
| sujetsFragiles | json | Titres des ressources fragiles |
| derniereActivite | datetime | |
| updatedAt | datetime | |

**Règle absolue** : écriture uniquement par ProgressionCalculator via EventSubscribers.

---

## 4. Value Objects — PHP Backed Enums

```php
NiveauMaitrise    : DEBUTANT | INTERMEDIAIRE | AVANCE
PlanUtilisateur   : GRATUIT | PREMIUM
ModeAccompagnement: SOCRATIQUE | MIXTE | EXPLICATIF
StatutParcours    : BROUILLON | ACTIF | PAUSE | TERMINE | ABANDONNE
StatutRessource   : A_FAIRE | EN_COURS | VUE | CONSOLIDEE
TypeRessource     : VIDEO | ARTICLE | COURS | PODCAST | LIVRE
TypeSession       : INITIALE | REVISION | REGENERATION
StatutSession     : EN_ATTENTE | GENERATION | PRET | COMPLETE | ERREUR
StatutEvaluation  : EN_ATTENTE | EVALUATION | EVALUEE | ERREUR
StatutProjet      : NON_DEMARRE | EN_COURS | TERMINE
StatutEtape       : VERROUILLEE | DISPONIBLE | EN_COURS | COMPLETE
TypeMicroEtape    : LECTURE | EXERCICE | OUTIL_EXTERNE | LIVRABLE
```

---

## 5. Relations clés

| De | Relation | Vers | Note |
|---|---|---|---|
| User | 1→n | Parcours | |
| Parcours | n→1 | Domaine | |
| Parcours | 1→n | Ressource | |
| Parcours | 1→1 | ProjetFilRouge | |
| Parcours | 1→1 | Progression | |
| Ressource | 1→n | TraceApprentissage | |
| Ressource | 1→n | SessionConsolidation | |
| Ressource | 1→n | RevisionSpacee | |
| TraceApprentissage | 1→n | SessionConsolidation | |
| SessionConsolidation | self? | SessionConsolidation | parent_session |
| SessionConsolidation | 1→n | Question | 3/session en V1 |
| SessionConsolidation | 1→1 | Exercice | |
| ProjetFilRouge | 1→n | MicroEtape | |

---

## 6. Règles métier à garantir côté service

- TraceApprentissage.user = propriétaire du Parcours
- RevisionSpacee.user = propriétaire du Parcours
- Régénération = nouvelle SessionConsolidation (parent_session obligatoire si REGENERATION)
- MicroEtape VERROUILLEE→DISPONIBLE : géré par MicroEtapeService
- Progression : écriture uniquement par ProgressionCalculator
- Ressource CONSOLIDEE si score moyen >= 3/5

---

## 7. Index recommandés

```
User           : email (unique), deleted_at
Parcours       : user_id, statut, deleted_at
Ressource      : parcours_id, statut, ordre
TraceApp.      : ressource_id, user_id
SessionConso.  : ressource_id, statut, type
Question       : session_id, ordre
RevisionSpacee : user_id, date_prevue, completee_at
MicroEtape     : projet_id, statut, ordre
Progression    : parcours_id (unique)
```

---

## Ajustements V1 — Implémentation réelle

### Table `users` (PostgreSQL reserved word)
```php
#[ORM\Entity]
#[ORM\Table(name: 'users')]  // OBLIGATOIRE — 'user' est réservé en PostgreSQL
class User { ... }
```
Si la table `user` existe déjà : `ALTER TABLE "user" RENAME TO users`

### Domaine — champ `label` (pas `nom`)
Le champ s'appelle `label` avec getter `getLabel()`. Dans Twig : `{{ domaine.label }}`, dans ParcoursType : `'choice_label' => 'label'`.

### SessionConsolidation ↔ Exercice — OneToOne singulier
```php
// CORRECT
$session->getExercice(): ?Exercice   // singulier
// FAUX — n'existe pas
$session->getExercices()
```

### Constructeurs exacts

```php
// Question — $ordre est int, PAS string ou type
new Question($session, $texte, $index + 1)
// ($type de QuestionDTO n'est PAS un arg du constructeur)

// MicroEtape — ordre des args strict
new MicroEtape($projet, $titre, $description, TypeMicroEtape::LECTURE, $ordre)
// Logique interne : ordre=1 → DISPONIBLE + debloqueeAt=now ; autres → VERROUILLEE

// ProjetFilRouge — 4 args obligatoires
new ProjetFilRouge($parcours, $titre, $description, PromptVersions::PARCOURS_V1)
```

### ExerciceDTO — propriété `outilSuggere` (pas `outil`)
```php
// CORRECT
$dto->exercice->outilSuggere->nom
// FAUX
$dto->exercice->outil->nom
```
`Exercice` n'a pas de méthode `setCriteresReussite()` — ignorer ce champ du DTO.

### Progression — getter `getScoreConsolidation()`
```twig
{# CORRECT #}
{{ parcours.progression.scoreConsolidation }}
{# FAUX #}
{{ parcours.progression.score }}
```

### Enums — valeurs minuscules
```php
StatutSession::EN_ATTENTE->value  // 'en_attente'
StatutParcours::BROUILLON->value  // 'brouillon'
// etc. — toujours minuscules dans les comparaisons Twig
```
