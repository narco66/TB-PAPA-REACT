# Module Audit interne IGS

**Inspection Générale des Services — TB-PAPA CEEAC**

Cycle complet : Plan annuel → Missions (lettre, périmètre, équipe, calendrier) → Constats → Recommandations → Suivi de mise en œuvre.

---

## 1. Conformité normative

| Référentiel | Application |
| --- | --- |
| **IIA — IPPF Standards** | Standard 1000 (mission/charte), 1100 (indépendance), 2010 (planification annuelle), 2200 (planification de la mission), 2400 (communication), 2500 (suivi des progrès) |
| **IFACI — Cadre de référence** | Méthodologie d'audit, classification gravité, structuration des recommandations |
| **ISO 19011** | Lignes directrices pour l'audit des systèmes de management (preuves, équipe, rapport) |
| **COSO Internal Control** | Composantes contrôle, évaluation, information, pilotage |

---

## 2. Architecture des données

6 tables — préfixe `audit_` — soft deletes + activity log :

```
audit_plans                      Programme annuel (unicité par année)
  └── audit_missions             Missions individuelles (unicité du code)
        ├── audit_mission_equipe Pivot user × rôle (chef, senior, auditeur, observateur, expert)
        └── audit_constats       Constats (gravité, nature, cause racine, impact)
              └── audit_recommandations  Recommandations (priorité, échéance, responsable)
                    └── audit_suivis_recommandations  Suivis périodiques (état, %, blocages)
```

### Énumérations métier

| Entité | Champ | Valeurs |
| --- | --- | --- |
| Plan | `statut` | projet · soumis · valide · execute · cloture · archive |
| Mission | `type` | financier · conformite · performance · systeme_information · organisationnel · thematique · suivi |
| Mission | `priorite` | critique · haute · moyenne · basse |
| Mission | `statut` | planifiee · lettre_emise · en_cours · projet_rapport · rapport_definitif · cloturee · annulee |
| Constat | `gravite` | critique · majeur · moyen · mineur · observation |
| Constat | `nature` | non_conformite · risque · inefficacite · inefficience · controle_insuffisant · bonne_pratique · autre |
| Recommandation | `priorite` | urgente · haute · moyenne · basse |
| Recommandation | `statut` | ouverte · planifiee · en_cours · mise_en_oeuvre · verifiee · rejetee · abandonnee |
| Suivi | `etat_avancement` | non_demarre · en_cours · realise · bloque · abandonne |

---

## 3. Sécurité et permissions

9 permissions dédiées (préfixe `audit_interne.`) :

| Permission | Rôle |
| --- | --- |
| `audit_interne.view` | Lecture du module |
| `audit_interne.plan_create` | Création/édition de plans |
| `audit_interne.plan_validate` | Validation des plans soumis |
| `audit_interne.mission_create` | Création/édition de missions |
| `audit_interne.mission_execute` | Exécution terrain (transitions) |
| `audit_interne.mission_close` | Clôture des missions |
| `audit_interne.constat_create` | Saisie des constats |
| `audit_interne.recommandation_create` | Formulation des recommandations |
| `audit_interne.suivi_create` | Enregistrement des suivis |

### Matrice RACI

| Rôle | Permissions audit |
| --- | --- |
| `audit_interne` | Toutes (auditeur interne IGS) |
| `admin_technique` | Toutes (via `Permission::all()`) |
| Autres rôles | Aucune par défaut |

---

## 4. Cycle de vie

### 4.1 Plan d'audit annuel (IIA 2010)

```
projet → soumis → valide → execute → cloture → archive
```

Le plan annuel est rédigé par l'IGS, soumis à la Direction générale, puis validé. La validation enregistre `valide_par_id` + `valide_at`.

### 4.2 Mission (IIA 2200)

```
planifiee → lettre_emise → en_cours → projet_rapport → rapport_definitif → cloturee
                                                                         ↘ annulee
```

Chaque mission a :
- Une lettre de mission (texte ou référence GED)
- Une équipe (chef + auditeurs avec rôles)
- Un calendrier (prévu / réel)
- Un département ou direction audité

### 4.3 Constats et recommandations (IIA 2400)

Chaque constat possède :
- Code unique par mission
- Description factuelle + preuves
- **Cause racine** (analyse 5 pourquoi)
- **Impact** (risque financier, opérationnel, image)

Une ou plusieurs recommandations peuvent être attachées à un constat. La méthode `estEnRetard()` détecte automatiquement les recommandations dont l'échéance est dépassée et qui ne sont ni vérifiées ni rejetées ni abandonnées.

### 4.4 Suivi de mise en œuvre (IIA 2500)

Le suivi périodique met à jour automatiquement :
- `pourcentage_avancement` de la recommandation
- `statut` selon l'état d'avancement du suivi :

| Suivi (`etat_avancement`) | Recommandation (`statut`) |
| --- | --- |
| non_demarre | ouverte |
| en_cours | en_cours |
| realise | mise_en_oeuvre |
| bloque | en_cours (avec `blocages` renseignés) |
| abandonne | abandonnee |

---

## 5. Routes (`/audit/*`)

```
GET   /audit                            Dashboard
GET   /audit/plans                      Liste plans
GET   /audit/plans/create               Formulaire plan
POST  /audit/plans                      Création
GET   /audit/plans/{plan}               Détail + missions
POST  /audit/plans/{plan}/valider       Validation (rôle dédié)

GET   /audit/missions                   Liste filtrable
GET   /audit/missions/create            Formulaire mission
POST  /audit/missions                   Création (avec équipe)
GET   /audit/missions/{mission}         Détail + constats inline

POST  /audit/constats                   Création constat
GET   /audit/constats/{constat}         Détail + recommandations inline

GET   /audit/recommandations            Liste globale filtrée
POST  /audit/recommandations            Création
GET   /audit/recommandations/{reco}     Détail + suivis

POST  /audit/suivis                     Création suivi (met à jour la reco)
```

---

## 6. Tests

`tests/Feature/Audit/AuditInterneTest.php` — **12 tests, 28 assertions, 100% passing** :

1. Dashboard accessible aux auditeurs
2. Dashboard refusé aux non-auditeurs
3. Création plan annuel
4. Année plan unique
5. Création mission avec équipe pivot
6. Création constat dans mission
7. Code constat unique par mission
8. Création recommandation + suivi avec mise à jour auto
9. Suivi `realise` passe la reco en `mise_en_oeuvre`
10. Validation plan réservée au rôle validateur
11. Listes accessibles aux utilisateurs avec droit `view`
12. `estEnRetard()` détecte correctement les recommandations en retard

---

## 7. Indicateurs IGS clés (Dashboard)

- Nombre de plans, plan en cours
- Missions par statut (en cours, clôturées)
- Constats par gravité (critiques)
- Recommandations ouvertes
- **Recommandations en retard** (échéance dépassée, statut non clos)

---

## 8. Évolutions prévues (P2/P3)

- Génération PDF du rapport de mission (lettre + corps + annexes)
- Workflow de visa (auditeur → chef mission → IGS → direction)
- Tableau de bord par auditeur (charge, missions en cours)
- Évaluation des auditeurs (notation par mission)
- Plan d'action annuel consolidé tous secteurs
- Export Excel des recommandations en retard pour relances trimestrielles

---

**Module livré le 2026-05-27** — Conforme IIA/IPPF · IFACI · ISO 19011 · COSO.
