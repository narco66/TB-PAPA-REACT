# TB-PAPA-CEEAC — Audit RBM/GAR & Modèle d'Import Excel

**Version** : 1.0 — **Date** : 2026-05-26
**Référence CDC** : Cahier des Charges Fonctionnel TB-PAPA-CEEAC, Sections 1, 2, 3, 5 et 6
**Statut** : Document de référence pour l'import institutionnel du PAPA

---

## Sommaire

- [Partie A — Cartographie de la chaîne RBM implémentée](#partie-a)
- [Partie B — Modèle d'import Excel TB-PAPA-Import.xlsx](#partie-b)
- [Partie C — Dictionnaire de données feuille par feuille](#partie-c)
- [Partie D — Règles d'importation et de validation](#partie-d)
- [Partie E — Mapping Excel ↔ Base de données](#partie-e)
- [Partie F — Recommandations d'amélioration](#partie-f)

---

<a name="partie-a"></a>
## A. Cartographie de la chaîne RBM implémentée

### A.1 Vue d'ensemble de la chaîne

L'application TB-PAPA-CEEAC met en œuvre une **chaîne RBM/GAR à 6 niveaux principaux** structurée hiérarchiquement, conforme aux principes de la Gestion Axée sur les Résultats (GAR) et au CDC institutionnel CEEAC :

```
PAPA (année N)
 └── Action prioritaire (Politique sectorielle, mandat Commissaire / Direction d'appui)
       └── Objectif immédiat (Intention opérationnelle court terme)
             └── Résultat attendu (Output / Outcome — changement mesurable)
                   ├── Indicateur (KPI normalisé : baseline → cible)
                   │     └── Valeur observée (saisie périodique)
                   └── Activité (action concrète planifiée — Gantt)
                         └── Tâche (décomposition opérationnelle)
                               └── Sous-tâche (récursivité)

Entités transversales (polymorphic, rattachables à plusieurs niveaux) :
 ── Budget (CEEAC | Partenaire) + Mouvements budgétaires
 ── Document (GED, 5 catégories) — "Pas de document, pas de résultat"
 ── Alerte (info | attention | critique) + Notifications
 ── Validation (workflow RACI à 7 étapes)
```

### A.2 Correspondance avec la nomenclature RBM standard

| Nomenclature RBM internationale | Entité implémentée dans TB-PAPA-CEEAC | Niveau |
|---|---|---|
| Objectif global (Impact) | *(Implicite dans le libellé du PAPA)* | 0 — Stratégique |
| Objectif de développement | Description PAPA + justification action | 0 — Stratégique |
| Axe stratégique | *(Implicite : portée par le Département technique du Commissaire)* | 0 — Stratégique |
| Programme / Domaine | `Departement` (technique) ou `Direction` (appui) | 1 — Sectoriel |
| Action / Produit (Output) | `ActionPrioritaire` puis `ResultatAttendu` (type=output) | 2 — Opérationnel |
| Sous-produit | *(Non modélisé séparément — porté par `Activite`)* | — |
| Effet immédiat (Outcome) | `ResultatAttendu` (type=outcome) | 3 — Effet |
| Activité | `Activite` | 4 — Exécution |
| Sous-activité / Tâche | `Tache` (récursif via `tache_parente_id`) | 5 — Exécution |
| Indicateur de performance | `Indicateur` | KPI |
| Cible | `Indicateur.cible` | KPI |
| Source de vérification | `Indicateur.source_donnees` + `Document` (GED) | Preuve |
| Responsable | `User` rattaché via `responsable_id` / `point_focal_id` / `direction_id` | RACI |
| Budget / Coût | `Budget` polymorphique (Action OU Activité) | Finance |
| Périodicité | `Indicateur.frequence_collecte` | Temps |
| Statut / Avancement | Champ `statut` + `avancement` sur chaque niveau | Suivi |

### A.3 Table récapitulative des entités

| # | Table | Rôle RBM | Parent obligatoire | Clé unique fonctionnelle |
|---|---|---|---|---|
| 1 | `papas` | Référentiel annuel racine | — | (annee, version) |
| 2 | `actions_prioritaires` | Priorité stratégique sectorielle | papa | (papa_id, code) |
| 3 | `objectifs_immediats` | Intention opérationnelle | action_prioritaire | (action_prioritaire_id, code) |
| 4 | `resultats_attendus` | Changement mesurable (output/outcome) | objectif_immediat | (objectif_immediat_id, code) |
| 5 | `indicateurs` | KPI normalisé | resultat_attendu | (resultat_attendu_id, code) |
| 6 | `valeurs_indicateurs` | Saisie périodique | indicateur | (indicateur, date_observation) |
| 7 | `activites` | Exécution planifiée (Gantt) | action_prioritaire | (action_prioritaire_id, code) |
| 8 | `taches` | Décomposition opérationnelle | activite | — |
| 9 | `budgets` | Allocation financière | papa + budgetable (Action/Activité) | — |
| 10 | `mouvements_budgetaires` | Historique financier | budget | — |
| 11 | `documents` | GED / preuve | documentable (polymorphique) | — |
| 12 | `alertes` | Signal automatique ou manuel | alertable (polymorphique) | — |
| 13 | `validations` | Workflow RACI | validable (polymorphique) | — |

### A.4 Référentiels institutionnels (préalables à tout import)

| Référentiel | Source | Modification autorisée |
|---|---|---|
| `users` (agents, Commissaires) | Seeder + Admin RH | Admin fonctionnel |
| `departements` (6 départements techniques) | Seeder | Admin fonctionnel |
| `directions` (technique + appui) | Seeder | Admin fonctionnel |
| `partenaires` (PTF) | Seeder ou Admin | Admin |
| `roles` + `permissions` | RolesPermissionsSeeder | Admin technique |

### A.5 Énumérations à respecter strictement (valeurs codées)

| Champ | Valeurs autorisées |
|---|---|
| `papas.statut` | brouillon, en_validation, valide, revise, cloture, archive |
| `actions_prioritaires.type` | **technique**, **appui_soutien** |
| `actions_prioritaires.priorite` | haute, moyenne, basse |
| `actions_prioritaires.statut` | proposee, validee, en_cours, realisee, suspendue, annulee |
| `resultats_attendus.type` | **output** (produit), **outcome** (effet) |
| `indicateurs.type` | quantitatif, qualitatif |
| `indicateurs.frequence_collecte` | mensuelle, trimestrielle, semestrielle, annuelle |
| `activites.statut` | planifiee, en_cours, realisee, suspendue, annulee |
| `activites.niveau_risque` | faible, moyen, eleve, critique |
| `budgets.source` | **ceeac**, **partenaire** |
| `directions.type` | technique, appui_soutien |

### A.6 Lacunes identifiées par rapport au RBM classique

Sont **non modélisés comme entités distinctes** mais peuvent être portés différemment :

| Concept manquant | Recommandation |
|---|---|
| **Objectif global** / Vision 2050 | Porté implicitement par le libellé et la description du PAPA. Si besoin, ajouter une table `orientations_strategiques` rattachée au PAPA ou utiliser le champ `description`. |
| **Axe stratégique** | Le **Département technique** (entité existante) joue ce rôle. Une action prioritaire est obligatoirement rattachée à un Département → l'axe est implicite. |
| **Sous-produit / Output intermédiaire** | Peut être modélisé via `Activite` (chaque activité produit un livrable). Pour une modélisation pure RBM, ajouter une couche `sous_resultats` entre `resultats_attendus` et `activites`. |
| **Chaîne de causalité explicite** | Non modélisée. Les liens hiérarchiques (FK) traduisent la chaîne, mais il n'y a pas de matrice de contribution croisée. |

**Verdict** : la chaîne actuelle est **cohérente, traçable et conforme** au CDC institutionnel CEEAC. Les concepts manquants peuvent être ajoutés ultérieurement sans refonte (architecture extensible).

---

<a name="partie-b"></a>
## B. Modèle d'import Excel `TB-PAPA-Import.xlsx`

### B.1 Principes de conception

1. **Un classeur = un PAPA** (une année N, une version).
2. **Codification fonctionnelle obligatoire** : chaque ligne possède un code métier unique qui sert de clé étrangère textuelle vers les niveaux parents. Cela évite la dépendance aux IDs base de données.
3. **Importation par passes successives** : feuilles importées dans l'ordre hiérarchique (PAPA → Actions → Objectifs → Résultats → Indicateurs → Activités → Budgets → Valeurs).
4. **Validation bloquante par étape** : aucun import partiel — soit tout passe, soit rien n'est inséré (transaction DB).
5. **Référentiels en lecture** : feuilles "REF_*" pour rappeler les valeurs codées attendues (départements, directions, utilisateurs, partenaires).
6. **Mode brouillon par défaut** : le PAPA importé reste en statut `brouillon` jusqu'à validation manuelle dans l'application.

### B.2 Structure du classeur (13 feuilles)

| # | Nom de la feuille | Catégorie | Lignes attendues |
|---|---|---|---|
| 1 | `0_LISEZ-MOI` | Documentation | Texte d'instruction |
| 2 | `1_PAPA` | Référentiel racine | 1 ligne |
| 3 | `2_ACTIONS` | Niveau 1 hiérarchique | N lignes (~10–30) |
| 4 | `3_OBJECTIFS` | Niveau 2 | N lignes |
| 5 | `4_RESULTATS` | Niveau 3 | N lignes |
| 6 | `5_INDICATEURS` | KPI | N lignes |
| 7 | `6_VALEURS_INDIC` | Saisies périodiques (optionnel) | N lignes |
| 8 | `7_ACTIVITES` | Niveau 4 (Gantt) | N lignes |
| 9 | `8_TACHES` | Niveau 5 | N lignes (optionnel) |
| 10 | `9_BUDGETS` | Allocations financières | N lignes |
| 11 | `REF_DEPARTEMENTS` | Référentiel (lecture seule) | Liste injectée |
| 12 | `REF_DIRECTIONS` | Référentiel (lecture seule) | Liste injectée |
| 13 | `REF_UTILISATEURS` | Référentiel (lecture seule) | Liste injectée |
| 14 | `REF_PARTENAIRES` | Référentiel (lecture seule) | Liste injectée |

### B.3 Ordre logique d'importation

```
        ┌─────────────────────────────────────────┐
        │  Pré-vérification : référentiels en DB  │
        │  (utilisateurs, départements, etc.)     │
        └────────────────┬────────────────────────┘
                         │
                         ▼
            ┌──────────────────────────┐
            │  Phase 1 : LECTURE       │ ← Parsing complet
            │  (toutes les feuilles)   │
            └────────────┬─────────────┘
                         │
                         ▼
            ┌──────────────────────────┐
            │  Phase 2 : VALIDATION    │ ← Codes, FK, enums,
            │  (toutes les règles)     │   unicité, formats
            └────────────┬─────────────┘
                         │ (KO → arrêt + rapport)
                         ▼
            ┌──────────────────────────┐
            │  Phase 3 : INSERTION     │ ← Transaction DB
            │  Ordre : PAPA → Actions  │
            │    → Objectifs → Résult. │
            │    → Indicateurs         │
            │    → Activités → Tâches  │
            │    → Budgets → Valeurs   │
            └────────────┬─────────────┘
                         │
                         ▼
            ┌──────────────────────────┐
            │  Phase 4 : RAPPORT       │
            │  + audit log centralisé  │
            └──────────────────────────┘
```

---

<a name="partie-c"></a>
## C. Dictionnaire de données — Feuilles détaillées

> **Convention** : colonnes en `gras` = obligatoires. Préfixe `[REF]` = code de référence vers un autre niveau.

### Feuille 1 — `1_PAPA` (1 ligne obligatoire)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| **CODE_PAPA** | Texte (16) | Oui | Pattern `PAPA-YYYY-vX.Y` | Identifiant interne (ex: `PAPA-2026-v1.0`) |
| **ANNEE** | Entier | Oui | 2024–2050 | Année de l'exercice |
| **VERSION** | Texte (16) | Oui | Défaut `1.0` | Version du PAPA |
| **LIBELLE** | Texte (255) | Oui | Min 5 caractères | Intitulé officiel |
| DESCRIPTION | Texte (5000) | Non | — | Description institutionnelle |
| PERIMETRE_INSTITUTIONNEL | Texte (5000) | Non | — | Portée organisationnelle |
| **DATE_DEBUT** | Date | Oui | Format YYYY-MM-DD | 1er jour de l'exercice |
| **DATE_FIN** | Date | Oui | > DATE_DEBUT | Dernier jour de l'exercice |
| STATUT_INITIAL | Énuméré | Non | `brouillon` (par défaut) | Statut à l'import |

**Exemple** :
```
PAPA-2026-v1.0 | 2026 | 1.0 | "PAPA 2026 de la CEEAC" | … | … | 2026-01-01 | 2026-12-31 | brouillon
```

---

### Feuille 2 — `2_ACTIONS` (10–30 lignes typiques)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_PAPA** | Texte | Oui | Doit exister en feuille 1 | Rattachement parent |
| **CODE_ACTION** | Texte (32) | Oui | Unique dans le PAPA | Code interne (ex: `AP-2026-001`) |
| **LIBELLE** | Texte (255) | Oui | — | Intitulé synthétique |
| DESCRIPTION | Texte (5000) | Non | — | Description longue |
| JUSTIFICATION | Texte (5000) | Non | — | Justification stratégique |
| **TYPE** | Énuméré | Oui | `technique` \| `appui_soutien` | Nature institutionnelle |
| PRIORITE | Énuméré | Non | `haute` \| `moyenne` \| `basse` | Défaut `moyenne` |
| [REF] **CODE_DEPARTEMENT** | Texte | Si TYPE=technique | Voir REF_DEPARTEMENTS | Département technique |
| [REF] CODE_DIRECTION | Texte | Si TYPE=appui_soutien | Voir REF_DIRECTIONS | Direction d'appui porteuse |
| [REF] EMAIL_COMMISSAIRE | Email | Non (auto) | Voir REF_UTILISATEURS | Commissaire responsable (déduit du département si vide) |
| [REF] EMAIL_RESPONSABLE | Email | Non | Voir REF_UTILISATEURS | Responsable opérationnel |
| DATE_DEBUT | Date | Non | Dans bornes PAPA | — |
| DATE_FIN | Date | Non | > DATE_DEBUT | — |
| ORDRE | Entier | Non | Défaut 0 | Ordre d'affichage |

**Règles métier** :
- Si `TYPE=technique` → `CODE_DEPARTEMENT` obligatoire.
- Si `TYPE=appui_soutien` → `CODE_DIRECTION` doit pointer vers une direction de type `appui_soutien`.

**Exemple** :
```
PAPA-2026-v1.0 | AP-2026-001 | "Accélération du Marché Commun" | … | "Vision 2050" | technique | haute | DAEC | | commissaire.aec@ceeac.org | | 2026-01-15 | 2026-12-15 | 1
```

---

### Feuille 3 — `3_OBJECTIFS` (1–3 par action typiquement)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_ACTION** | Texte | Oui | Doit exister en feuille 2 | Action parente |
| **CODE_OBJECTIF** | Texte (32) | Oui | Unique dans l'action | Code (ex: `OI-001`) |
| **LIBELLE** | Texte (255) | Oui | Min 10 caractères | Intitulé SMART |
| DESCRIPTION | Texte (5000) | Non | — | — |
| CONTRIBUTION_ATTENDUE | Texte (2000) | Non | — | % ou narratif de contribution à l'action |
| ORDRE | Entier | Non | Défaut 0 | — |

**Exemple** :
```
AP-2026-001 | OI-001 | "D'ici fin 2026, l'instrument unique de TVA harmonisée est adopté" | … | "Contribue à 60% à l'AP-2026-001" | 1
```

---

### Feuille 4 — `4_RESULTATS` (1–3 par objectif)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_OBJECTIF** | Texte | Oui | Doit exister en feuille 3 | Objectif parent |
| **CODE_RESULTAT** | Texte (32) | Oui | Unique dans l'objectif | Code (ex: `R-001`) |
| **LIBELLE** | Texte (255) | Oui | — | — |
| DESCRIPTION | Texte (5000) | Non | — | — |
| **TYPE_RESULTAT** | Énuméré | Oui | `output` \| `outcome` | Produit (livrable) ou Effet (changement) |
| **ANNEE_REFERENCE** | Entier | Oui | = ANNEE du PAPA | — |

**Exemple** :
```
OI-001 | R-001 | "Instrument TVA harmonisée adopté par les 11 États membres" | … | output | 2026
```

---

### Feuille 5 — `5_INDICATEURS` (1–5 par résultat)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_RESULTAT** | Texte | Oui | Voir feuille 4 | Résultat parent |
| **CODE_INDICATEUR** | Texte (32) | Oui | Unique dans le résultat | Code (ex: `IND-001`) |
| **LIBELLE** | Texte (255) | Oui | — | — |
| DEFINITION | Texte (5000) | Non | — | Définition précise |
| **TYPE** | Énuméré | Oui | `quantitatif` \| `qualitatif` | — |
| UNITE | Texte (32) | Non | `%`, `nombre`, `FCFA`, etc. | — |
| BASELINE | Décimal | Non | Situation de référence | — |
| **CIBLE** | Décimal | Oui (quantitatif) | > BASELINE conseillé | Valeur visée |
| DATE_BASELINE | Date | Non | — | Date d'établissement de la baseline |
| METHODE_CALCUL | Texte (5000) | Non | — | Formule, méthode |
| **FREQUENCE_COLLECTE** | Énuméré | Oui | `mensuelle` \| `trimestrielle` \| `semestrielle` \| `annuelle` | — |
| SOURCE_DONNEES | Texte (255) | Non | — | Source institutionnelle |
| [REF] EMAIL_RESPONSABLE | Email | Non | Voir REF_UTILISATEURS | Responsable de la collecte |

**Exemple** :
```
R-001 | IND-001 | "Nombre d'États membres ayant ratifié l'instrument TVA" | … | quantitatif | nombre | 0 | 11 | 2025-12-31 | "Comptage des ratifications notifiées au SG" | semestrielle | "SG CEEAC" | directeur.dc@ceeac.org
```

---

### Feuille 6 — `6_VALEURS_INDIC` (optionnelle — baseline + historique)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_INDICATEUR** | Texte | Oui | Voir feuille 5 | Indicateur parent |
| **DATE_OBSERVATION** | Date | Oui | YYYY-MM-DD | Date de mesure |
| PERIODE_LIBELLE | Texte (32) | Non | `T1 2026`, `S1 2026`, `Mars 2026` | Libellé humain |
| **VALEUR** | Décimal | Oui | — | Valeur observée |
| COMMENTAIRE | Texte (2000) | Non | — | — |
| SOURCE_VERIFICATION | Texte (255) | Non | — | Doc, rapport, base… |

---

### Feuille 7 — `7_ACTIVITES` (5–20 par action typiquement)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_ACTION** | Texte | Oui | Voir feuille 2 | Action parente |
| [REF] CODE_RESULTAT | Texte | Non | Voir feuille 4 | Lien vers résultat (optionnel) |
| **CODE_ACTIVITE** | Texte (32) | Oui | Unique dans l'action | Code (ex: `ACT-001`) |
| **LIBELLE** | Texte (255) | Oui | — | — |
| DESCRIPTION | Texte (5000) | Non | — | — |
| **DATE_DEBUT_PREVUE** | Date | Oui | Dans bornes PAPA | — |
| **DATE_FIN_PREVUE** | Date | Oui | ≥ DATE_DEBUT_PREVUE | — |
| NIVEAU_RISQUE | Énuméré | Non | `faible` \| `moyen` \| `eleve` \| `critique` | Défaut `faible` |
| EST_JALON | Booléen | Non | `oui` \| `non` | Défaut `non` |
| [REF] CODE_DIRECTION | Texte | Non | Voir REF_DIRECTIONS | Direction porteuse |
| [REF] EMAIL_RESPONSABLE | Email | Non | Voir REF_UTILISATEURS | Responsable hiérarchique |
| [REF] EMAIL_POINT_FOCAL | Email | Non | Voir REF_UTILISATEURS | Point focal opérationnel |
| ORDRE | Entier | Non | Défaut 0 | — |

---

### Feuille 8 — `8_TACHES` (optionnelle)

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| [REF] **CODE_ACTIVITE** | Texte | Oui | Voir feuille 7 | Activité parente |
| **LIBELLE** | Texte (255) | Oui | — | Description courte |
| DATE_DEBUT_PREVUE | Date | Non | — | — |
| DATE_FIN_PREVUE | Date | Non | — | — |
| [REF] EMAIL_ASSIGNE | Email | Non | Voir REF_UTILISATEURS | Assignataire |
| ORDRE | Entier | Non | — | — |

---

### Feuille 9 — `9_BUDGETS`

| Colonne | Type | Obligatoire | Validation | Description |
|---|---|---|---|---|
| **TYPE_RATTACHEMENT** | Énuméré | Oui | `action` \| `activite` | Type d'entité budgétisée |
| [REF] **CODE_ENTITE** | Texte | Oui | CODE_ACTION ou CODE_ACTIVITE | Référence à l'entité |
| **SOURCE** | Énuméré | Oui | `ceeac` \| `partenaire` | Source de financement |
| [REF] CODE_PARTENAIRE | Texte | Si SOURCE=partenaire | Voir REF_PARTENAIRES | Code partenaire (ex: `UE`, `AFD`) |
| DEVISE | Texte (8) | Non | Défaut `XAF` | XAF, USD, EUR… |
| **PREVISION** | Décimal | Oui | ≥ 0 | Budget prévu |
| ENGAGEMENT | Décimal | Non | ≤ PREVISION | Montant engagé |
| CONSOMMATION | Décimal | Non | ≤ ENGAGEMENT | Montant consommé |
| OBSERVATIONS | Texte (2000) | Non | — | — |

**Règle métier critique** : `CONSOMMATION ≤ ENGAGEMENT ≤ PREVISION` (sinon alerte automatique de dérive budgétaire).

**Exemple** :
```
action | AP-2026-001 | ceeac | | XAF | 200000000 | 80000000 | 35000000 | "Budget validé Conseil d'Administration"
action | AP-2026-001 | partenaire | UE | XAF | 500000000 | 200000000 | 75000000 | "Convention CEEAC-UE 2026"
```

---

### Feuilles `REF_*` (référentiels lecture seule)

Ces feuilles sont **pré-remplies automatiquement** par le générateur du template à partir de la base de données. Elles servent de **liste de valeurs** pour les colonnes `[REF]` des autres feuilles.

| Feuille | Colonnes | Source |
|---|---|---|
| `REF_DEPARTEMENTS` | code, libellé, commissaire | Table `departements` |
| `REF_DIRECTIONS` | code, libellé, type (technique/appui) | Table `directions` |
| `REF_UTILISATEURS` | email, nom, matricule, rôle principal | Table `users` |
| `REF_PARTENAIRES` | code, libellé, type | Table `partenaires` |

---

<a name="partie-d"></a>
## D. Règles d'importation et de validation

### D.1 Contrôles préalables (avant ouverture de la transaction)

1. **Présence des référentiels requis** : la BDD doit contenir au minimum les départements, directions et utilisateurs référencés.
2. **Validation du fichier Excel** :
   - Extension `.xlsx` (rejet `.xls`, `.csv`).
   - Présence des 13 feuilles attendues (les optionnelles peuvent être vides mais doivent exister).
   - En-têtes de colonnes conformes (1ère ligne).

### D.2 Contrôles bloquants (Phase de validation)

| Règle | Erreur retournée si KO |
|---|---|
| Code PAPA unique en DB pour l'année | `Le PAPA pour l'année 2026 existe déjà (CODE_PAPA=…). Importez une autre version.` |
| Chaque CODE_ACTION rattaché à un CODE_PAPA valide | `Action AP-X : CODE_PAPA=… inconnu dans la feuille 1.` |
| Chaque CODE_OBJECTIF rattaché à une action existante | `Objectif OI-X : CODE_ACTION=… inconnu.` |
| Chaque CODE_RESULTAT rattaché à un objectif existant | `Résultat R-X : CODE_OBJECTIF=… inconnu.` |
| Chaque CODE_INDICATEUR rattaché à un résultat | `Indicateur IND-X : CODE_RESULTAT=… inconnu.` |
| Chaque CODE_ACTIVITE rattaché à une action | `Activité ACT-X : CODE_ACTION=… inconnu.` |
| Type technique → CODE_DEPARTEMENT renseigné et existant | `Action AP-X : action technique sans département.` |
| Type appui_soutien → CODE_DIRECTION type appui | `Action AP-X : appui sans direction d'appui valide.` |
| Email utilisateur → existe et actif | `Utilisateur "x@ceeac.org" inconnu ou désactivé (ligne X feuille Y).` |
| Énumérations strictement respectées | `Valeur "moy" invalide pour PRIORITE (attendu : haute\|moyenne\|basse).` |
| Dates cohérentes (fin ≥ début) | `Action AP-X : DATE_FIN antérieure à DATE_DEBUT.` |
| Unicité des codes dans leur scope | `Code AP-001 utilisé 2 fois dans le PAPA.` |
| Budget : CONSOMMATION ≤ ENGAGEMENT ≤ PREVISION | `Budget ligne X : dérive (consommation > engagement).` *(non bloquant — génère une alerte)* |

### D.3 Stratégie transactionnelle

```php
DB::transaction(function () use ($data) {
    $papa = Papa::create($data['papa']);
    foreach ($data['actions'] as $a) ActionPrioritaire::create(...);
    foreach ($data['objectifs'] as $o) ObjectifImmediat::create(...);
    // … etc. dans l'ordre hiérarchique
});
```

En cas d'erreur métier ou DB, **rollback complet** — l'utilisateur reçoit un rapport d'erreurs et **rien n'est créé**.

### D.4 Sortie attendue de l'import

```
✓ PAPA 2026 v1.0 créé (statut: brouillon)
✓ 5 actions prioritaires
✓ 5 objectifs immédiats
✓ 5 résultats attendus (output: 4, outcome: 1)
✓ 12 indicateurs
✓ 23 activités (dont 3 jalons)
✓ 8 budgets (CEEAC: 5, Partenaires: 3)
✓ Total budget prévu : 4 320 000 000 FCFA
✓ Audit log : 56 entrées créées
```

---

<a name="partie-e"></a>
## E. Mapping Excel ↔ Base de données

| Feuille Excel | Table SQL | Colonne pivot (FK textuelle → ID) |
|---|---|---|
| `1_PAPA` | `papas` | `CODE_PAPA` → `id` mémorisé en mémoire |
| `2_ACTIONS` | `actions_prioritaires` | `CODE_PAPA` → `papa_id` |
| | | `CODE_DEPARTEMENT` → `departement_id` (via `departements.code`) |
| | | `CODE_DIRECTION` → `direction_id` (via `directions.code`) |
| | | `EMAIL_COMMISSAIRE` → `commissaire_id` (via `users.email`) |
| `3_OBJECTIFS` | `objectifs_immediats` | `CODE_ACTION` → `action_prioritaire_id` |
| `4_RESULTATS` | `resultats_attendus` | `CODE_OBJECTIF` → `objectif_immediat_id` |
| `5_INDICATEURS` | `indicateurs` | `CODE_RESULTAT` → `resultat_attendu_id` |
| `6_VALEURS_INDIC` | `valeurs_indicateurs` | `CODE_INDICATEUR` → `indicateur_id` |
| `7_ACTIVITES` | `activites` | `CODE_ACTION` → `action_prioritaire_id` |
| | | `CODE_RESULTAT` → `resultat_attendu_id` |
| `8_TACHES` | `taches` | `CODE_ACTIVITE` → `activite_id` |
| `9_BUDGETS` | `budgets` | `TYPE_RATTACHEMENT` + `CODE_ENTITE` → `budgetable_type/id` |
| | | `CODE_PARTENAIRE` → `partenaire_id` |

---

<a name="partie-f"></a>
## F. Recommandations d'amélioration

### F.1 Améliorations recommandées pour faciliter l'import

| # | Recommandation | Bénéfice |
|---|---|---|
| 1 | Ajouter une commande `tbpapa:import-papa {fichier.xlsx}` | Import par CLI batch |
| 2 | Ajouter une route web `/papa/import` avec upload + preview avant validation | UX SG, validation interactive |
| 3 | Générer le template via `tbpapa:generer-template-import` qui injecte les référentiels actuels | Toujours à jour |
| 4 | Ajouter une feuille `10_DEPENDANCES_GANTT` (CODE_ACTIVITE_A, CODE_ACTIVITE_B, type) | Support complet Gantt |
| 5 | Permettre l'import incrémental (`--mode=update`) sur PAPA brouillon | Itérations sans perte |
| 6 | Validation pré-import via aperçu HTML (style Inertia) | Réduire les rejets |

### F.2 Améliorations RBM optionnelles (au cas où)

| # | Concept | Recommandation |
|---|---|---|
| 1 | **Axe stratégique** explicite | Ajouter table `axes_strategiques(papa_id, code, libelle)` rattachée à `actions_prioritaires`. |
| 2 | **Sous-produit** | Ajouter table `sous_resultats(resultat_attendu_id, code, libelle)` insérée entre Résultat et Activité. |
| 3 | **Chaîne de causalité** | Pivot N-N `contribution_resultats(resultat_amont_id, resultat_aval_id, poids)` pour matrice de contribution. |
| 4 | **Hypothèses & risques** | Champ `hypotheses` + `risques` (JSON ou table dédiée) sur résultats attendus pour théorie du changement complète. |

### F.3 Conformité aux meilleures pratiques internationales

- **OCDE/CAD** : compatible avec la nomenclature output/outcome/impact (champ `type` sur résultats).
- **UNDG/RBM** : structure SMART permise via objectifs immédiats.
- **EU DG-INTPA** : possibilité d'export vers cadre logique standard avec ajout des hypothèses (F.2.4).
- **ISO 21500 (gestion de projet)** : Gantt + dépendances PERT couverts.
- **COSO ERM** : alertes + niveau de risque par activité (Section 6.10 du CDC).

---

## Conclusion

La chaîne RBM de TB-PAPA-CEEAC est **complète, cohérente et conforme** au CDC. Le modèle d'import Excel proposé permet **une importation fiable, transactionnelle et auditable** de l'intégralité d'un PAPA en un seul fichier `.xlsx`.

**Livrables associés** :
- Commande `php artisan tbpapa:generer-template-import` — produit `storage/app/templates/TB-PAPA-Import-Template.xlsx` à jour.
- Commande `php artisan tbpapa:import-papa {fichier.xlsx}` — importe un PAPA depuis un fichier rempli.
- Service `App\Services\Import\PapaImportService` — logique métier d'import.

— *Document généré par l'audit RBM/GAR — TB-PAPA-CEEAC v1.0*
