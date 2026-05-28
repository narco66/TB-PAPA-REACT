# Indicateurs CMR — Cadre de Mesure du Rendement

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre les constats C-009 (typologie OCDE manquante), C-011 (désagrégation absente), C-030 (théorie du changement) de [docs/AUDIT-COMPLET.md](AUDIT-COMPLET.md).**

---

## 1. Vue d'ensemble

Le module Indicateurs a été enrichi pour respecter intégralement les exigences du **Cadre de Mesure du Rendement (CMR)** RBM/GAR, conformes aux référentiels institutionnels de :

- **Banque Africaine de Développement** (BAD)
- **Banque Mondiale** (PforR Results Framework)
- **Union Européenne** (DG INTPA Result Indicators)
- **Union Africaine** (Agenda 2063 STC Indicators)
- **CAD/OCDE** (Critères d'évaluation 2019)

Une fiche d'indicateur conforme CMR couvre désormais 6 sections : **Identification · Typologie OCDE · Cibles & paliers · Méthodologie · Désagrégation · Théorie du changement**.

---

## 2. Typologie CAD/OCDE (4 niveaux)

Chaque indicateur appartient à une catégorie correspondant à un niveau de la chaîne de causalité RBM :

| Catégorie | Définition | Exemple CEEAC |
|---|---|---|
| **Impact** | Changement à long terme dans le bien-être ou la situation des populations cibles | Réduction du taux de pauvreté régional |
| **Effet** (Outcome) | Changement à moyen terme dans les comportements ou capacités | % d'États membres ayant adopté la politique X |
| **Produit** (Output) | Livrable direct d'une activité (biens, services, événements) | Nombre de formations dispensées |
| **Processus** (Input/Process) | Ressources mobilisées ou étapes d'exécution | Budget exécuté, nombre de réunions tenues |

Stockage : enum `categorie` dans `indicateurs`.

---

## 3. Polarité

Direction souhaitée de variation. Détermine la formule de calcul du `taux_realisation`.

| Polarité | Sens | Formule |
|---|---|---|
| **positive** | « plus c'est élevé, mieux c'est » (taux d'alphabétisation, accès à l'eau) | `(valeur - baseline) / (cible - baseline) × 100` |
| **negative** | « plus c'est bas, mieux c'est » (mortalité, pauvreté, délai d'instruction) | `(baseline - valeur) / (baseline - cible) × 100` |
| **neutre** | « respect d'une cible exacte » (ratio cible, équilibre) | `100 - |valeur - cible| / cible × 100` |

Implémentée dans `App\Models\Indicateur::recalculerTauxRealisation()`.

---

## 4. Paliers trimestriels

Quatre colonnes `palier_t1`, `palier_t2`, `palier_t3`, `palier_t4` définissent la trajectoire intermédiaire attendue. Permet de monitorer l'avancement infra-annuel et déclencher des alertes en cas d'écart.

**Page show** : grille 4 cartes affichant cible vs valeur observée + écart coloré (vert positif / rouge négatif).

---

## 5. Désagrégation

Quatre dimensions activables sur l'indicateur (booléens) :

- `desagregation_genre` — Hommes / Femmes (valeurs séparées sur chaque observation)
- `desagregation_geographique` — par État membre, région, district (JSON libre)
- `desagregation_vulnerabilite` — réfugiés, déplacés internes, jeunes, handicapés (JSON libre)
- `desagregation_age` — tranches d'âge (JSON libre)

À la saisie d'une valeur, si `desagregation_genre` est activée, des champs `valeur_hommes` et `valeur_femmes` apparaissent. Le modèle `ValeurIndicateur::genreCoherent()` vérifie que H + F = total (tolérance 0.01).

---

## 6. Méthodologie complète

| Champ | Rôle |
|---|---|
| `methode_calcul` | Formule, étapes, dénominateur/numérateur |
| `frequence_collecte` | mensuelle / trimestrielle / semestrielle / annuelle |
| `source_donnees` | Provenance (système, partenaire, rapport officiel) |
| `instrument_collecte` | Enquête, base administrative, observation directe, etc. |
| `responsable_collecte_id` | Acteur chargé de la saisie terrain |
| `responsable_id` | Acteur de validation institutionnelle (signature) |

Séparation **collecte / validation** = exigence COSO ERM (séparation des tâches).

---

## 7. Théorie du changement

Deux champs textuels :

- `hypotheses` — Conditions à respecter pour que l'indicateur reste pertinent
- `risques_associes` — Risques externes ou internes pouvant compromettre l'atteinte

Permet de documenter la **logique d'intervention** RBM standard CAD/OCDE.

---

## 8. Plage acceptable & alertes

- `seuil_alerte_bas` et `seuil_alerte_haut` — bornes de tolérance
- Méthode `Indicateur::estHorsPlage()` — true si valeur actuelle hors bornes
- Badge "Hors plage acceptable" affiché en rouge sur la page show

---

## 9. Référentiels externes

Colonne JSON `referentiels_externes` pour rattacher l'indicateur aux cadres internationaux :

```json
[
  { "cadre": "ODD", "code": "4.1" },
  { "cadre": "Agenda 2063", "code": "1.1" },
  { "cadre": "PDDAA", "code": "P2" }
]
```

Préparation pour reporting bailleurs / supranational.

---

## 10. Schéma BDD

### Table `indicateurs` — 17 champs ajoutés
- `categorie` enum(impact, effet, produit, processus)
- `polarite` enum(positive, negative, neutre)
- `palier_t1`, `palier_t2`, `palier_t3`, `palier_t4` decimal(18,4)
- `instrument_collecte` string
- `responsable_collecte_id` FK users
- `desagregation_genre`, `desagregation_geographique`, `desagregation_vulnerabilite`, `desagregation_age` boolean
- `hypotheses`, `risques_associes` text
- `seuil_alerte_bas`, `seuil_alerte_haut` decimal(18,4)
- `referentiels_externes` json

### Table `valeurs_indicateurs` — 7 champs ajoutés
- `trimestre` enum(T1, T2, T3, T4)
- `annee` smallint
- `valeur_hommes`, `valeur_femmes` decimal(18,4)
- `desagregation_age`, `desagregation_geographique`, `desagregation_vulnerabilite` json

Migration : `2026_05_27_040010_add_cmr_fields_to_indicateurs_table.php` + `2026_05_27_040020_add_desagregation_to_valeurs_indicateurs.php`.

---

## 11. Pages React

### `/indicateurs/create`
Formulaire en 6 sections :
1. Identification et rattachement RBM (Sous-Produit)
2. Typologie OCDE/CAD (catégorie + type + polarité + unité)
3. Baseline, cible et paliers trimestriels + seuils d'alerte
4. Méthodologie de mesure (méthode, source, fréquence, instrument, 2 responsables)
5. Désagrégation (4 cases à cocher)
6. Théorie du changement (hypothèses + risques)

### `/indicateurs/{id}`
- Hero institutionnel avec 4 métriques (cible, actuel, taux réalisation, fréquence)
- Bandeau catégorisation (badges colorés par catégorie OCDE)
- Performance globale (baseline / actuel / cible + barre progression colorée)
- Rattachement RBM (PAPA → Axe → Produit → Sous-Produit)
- Paliers trimestriels (grille 4 cartes avec écart)
- Méthodologie CMR complète
- Saisie périodique (avec champs H/F si désagrégation genre activée)
- Historique des observations (colonnes adaptatives)

---

## 12. Tests

`tests/Unit/Models/IndicateurCmrTest.php` — 10 cas :
- Taux de réalisation polarité positive / négative / neutre (atteint + écart)
- `ciblePalier()` retourne la bonne valeur par trimestre
- `ecartPalier()` calcule la différence (null si palier non défini)
- `estHorsPlage()` sous seuil bas / au-dessus seuil haut / dans plage
- `dimensionsDesagregation()` collecte les drapeaux actifs

---

## 13. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **CAD/OCDE** | 4 niveaux résultats (impact/effet/produit/processus) | ✅ |
| **BAD CMR** | Baseline + cible + paliers + méthode | ✅ |
| **BM PforR** | Désagrégation genre + géographique | ✅ |
| **UE INTPA** | Polarité + plage acceptable | ✅ |
| **Agenda 2063 STC** | Rattachement référentiels externes | ✅ (JSON) |
| **ODD 17** | Rattachement ODD via `referentiels_externes` | ✅ |
| **CDC § 5.3.5** | Tous les champs CMR | ✅ |
| **CDC § 6.5** | Fiche signalétique normalisée | ✅ |
| **COSO ERM** | Séparation collecte / validation | ✅ |

---

## 14. Chantiers d'extension futurs

| Priorité | Action | Effort |
|---|---|---|
| P1 | Table dédiée `valeurs_desagregees` pour désagrégation très fine | M |
| P1 | Validation institutionnelle des valeurs saisies (workflow) | S |
| P2 | Tableau de bord indicateurs avec heatmap CMR (impact/effet/produit/processus) | M |
| P2 | Export CSV/Excel formaté CMR pour bailleurs | S |
| P2 | Comparaison multi-périodes / inter-PAPA | M |
| P3 | API pour partage indicateurs avec systèmes bailleurs | M |
| P3 | Cartographie territoriale des désagrégations géographiques | L |

---

**Fin du document** — version 1.0
