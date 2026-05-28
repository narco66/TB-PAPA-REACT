# Import Excel multi-feuilles — TB-PAPA-CEEAC

**Version** : 4.0 (Phases 1 + 2 + 3 + 4 + 5 + 6) · **Date** : 27 mai 2026
**Approche** : évolution non destructive du module d'import budgétaire existant.

## ⚡ Quoi de neuf en v4.0 (Phases 5 + 6)
- **Phase 5 — Rollback transactionnel par feuille** : annule un import en soft-deletant les ressources créées
  - Migration : `originated_from_import_id` nullable sur axes/produits/sous_produits/activites/taches/indicateurs/budget_lignes
  - Services Import propagent automatiquement la traçabilité à la création
  - Service `ImportRollbackService` : soft-delete dans l'ordre inverse de la cascade RBM
  - Endpoint `POST /budget/imports/{import}/rollback` (admin technique uniquement)
  - UI : bouton "↶ Annuler" dans l'historique pour les imports en statut `reussi`
  - Ressources soft-deletées (deleted_at) — récupérables via `SoftDeletes::restore()`
- **Phase 6 — Jobs asynchrones queue** : support fichiers volumineux
  - Job `ProcessMultiSheetImportJob` (Queueable, timeout 30 min, tries=1 pour cohérence)
  - Endpoint `GET /budget/imports/{import}/statut` (JSON, polling-ready)
  - Activation : configurer `QUEUE_CONNECTION=database|redis|sqs` dans `.env`
  - Par défaut (`sync`), comportement inchangé

## v3.0 (Phases 3 + 4)
- **Phase 3 — Mapping persisté** : sauvegarde/réutilisation/suppression des mappings de colonnes par utilisateur
  - Endpoint `POST /budget/imports/mappings` (sauvegarde) + `DELETE /budget/imports/mappings/{mapping}` (suppression)
  - UI dans la page d'import : bouton "💾 Sauver mapping" par feuille analysée + tableau des mappings enregistrés
  - Mappings privés ou partagés à toute l'équipe
- **Phase 4 — Rapport d'erreurs XLSX riche** : nouveau format coloré
  - Endpoint `GET /budget/imports/{import}/rapport-erreurs.xlsx`
  - Feuille "Synthèse" : totaux par feuille × gravité
  - Feuille "Erreurs détaillées" : lignes colorées (rouge=critique, orange=avertissement, bleu=info)
  - Liens cliquables dans l'historique : "Rapport CSV" ou "XLSX coloré"

## v2.0 (Phase 1 + Phase 2)
- Import RBM complet : 6 services spécialisés (Axes, Produits, Sous-Produits, Activités, Tâches, Indicateurs)
- Orchestrateur en cascade : ordonne automatiquement les feuilles dans le bon ordre (Axes → Tâches)
- Endpoint `POST /budget/imports/multi-feuilles` avec sélection de feuilles + dry-run
- UI de sélection des feuilles importables après analyse
- Tests Feature complets (cascade + ref inexistante + dry-run + endpoint HTTP)
- Mode upsert : code existant → update ; sinon création (codification automatique via observer)

---

## 1. Vue d'ensemble

Le module d'import a été enrichi pour préparer la prise en charge de fichiers Excel **multi-feuilles** dans la chaîne RBM officielle CEEAC (Axe → Produit → Sous-Produit → Activité → Tâche + Indicateurs + Budget).

**Cette version (v1.0)** livre les **fondations techniques** :
- Analyse non-destructive d'un fichier multi-feuilles
- Détection automatique des types de feuilles
- Mapping suggéré entre en-têtes Excel et champs canoniques
- Tables d'historique étendues (parent/enfant + mapping persisté + erreurs structurées)

**Phases ultérieures** (planifiées, voir §7) :
- Import effectif des feuilles Axes/Produits/Sous-Produits/Activités/Tâches/Indicateurs
- Mapping persisté réutilisable par utilisateur
- Rapport d'erreurs XLSX riche (couleurs, liens, suggestions de correction)
- Reprise/rollback transactionnel par feuille

---

## 2. Approche non destructive

Conformément à l'exigence du cahier des charges :

✅ **Le BudgetImportService existant n'a pas été modifié** — il continue à fonctionner exactement comme avant.
✅ **Le format de fichier mono-feuille reste pris en charge** (compatibilité totale).
✅ **Les colonnes `parent_import_id`, `feuille_source`, `type_donnees`, `options` ajoutées à `budget_imports` sont toutes `nullable`** — aucune donnée existante n'est altérée.
✅ **Les tables `budget_import_mappings` et `budget_import_erreurs` sont nouvelles** — additives uniquement.
✅ **La page React conserve le formulaire d'import existant** — la section "Analyse" est purement additive et désactivée par défaut.

---

## 3. Architecture

```
Page React (3 étapes progressives)
  1. Upload fichier         → state local
  2. Analyser (optionnel)   → POST /budget/imports/analyser   (JSON, non destructif)
  3. Importer               → POST /budget/imports             (Inertia, écrit en base)

Backend
  ExcelAnalyzerService          ← lecture + détection (sans persistance)
  BudgetImportService           ← INCHANGÉ, import mono-feuille
  BudgetImportTemplateService   ← INCHANGÉ, génération template

  BudgetImport (model étendu)
    ├── parent_import_id  (nullable) — groupe les imports multi-feuilles
    ├── feuille_source    (nullable) — nom de feuille pour les enfants
    ├── type_donnees      (nullable) — budget/axes/.../indicateurs
    └── options           (json nullable) — config import

  BudgetImportMapping (nouveau) — mapping persisté par user × type_donnees
  BudgetImportErreur  (nouveau) — erreurs structurées indexables
```

---

## 4. Endpoints

| Méthode | URI | Rôle | Permission |
|---|---|---|---|
| GET | `/budget/imports` | Page d'import (avec analyse + historique + mappings) | `import_budget` |
| GET | `/budget/imports/modele` | Téléchargement modèle Excel officiel | `import_budget` |
| **POST** | **`/budget/imports/analyser`** | **Analyse non destructive — retourne JSON décrivant les feuilles** | `import_budget` |
| **POST** | **`/budget/imports/mappings`** | **Sauvegarde un mapping réutilisable** | `import_budget` |
| POST | `/budget/imports` | Exécution import (existant, INCHANGÉ) | `import_budget` |
| GET | `/budget/imports/{import}/rapport-erreurs` | Export rapport erreurs CSV (lit table dédiée OU journal JSON) | `import_budget` |
| GET | `/budget/exports` | Export budget (existant) | `export_budget` |

Les routes en gras sont nouvelles.

---

## 5. Format JSON de l'endpoint `/analyser`

**Requête** : `multipart/form-data` avec `fichier` (XLSX/XLS/CSV, ≤ 25 Mo)

**Réponse 200** :
```json
{
  "succes": true,
  "analyse": {
    "fichier": "donnees-papa-2026.xlsx",
    "nb_feuilles": 4,
    "feuilles": [
      {
        "nom": "Axes",
        "index": 0,
        "type_detecte": "axes",
        "nb_lignes": 12,
        "nb_colonnes": 5,
        "en_tetes": ["Code", "Libellé", "Description", "Poids", "Département"],
        "mapping_suggere": {
          "Code": "code",
          "Libellé": "libelle",
          "Description": "description",
          "Poids": "poids",
          "Département": "departement"
        },
        "colonnes_inconnues": [],
        "champs_manquants": ["responsable"]
      }
    ]
  }
}
```

**Réponse 422** si fichier illisible :
```json
{ "succes": false, "message": "Impossible de lire le fichier : ..." }
```

---

## 6. Détection automatique du type de feuille

`ExcelAnalyzerService::devinerType()` applique deux stratégies en cascade :

1. **Heuristique sur le nom de feuille** (`HINTS_NOM_FEUILLE`) :
   - "Budget à importer" / "Lignes budgétaires" → `budget`
   - "Axes" / "Piliers" → `axes`
   - "Produits" / "Résultats" → `produits`
   - "Sous-produits" → `sous_produits`
   - "Activités" → `activites`
   - "Tâches" → `taches`
   - "Indicateurs" / "KPI" / "CMR" → `indicateurs`

2. **Score sur les en-têtes** (`SYNONYMS`) : si ≥ 2 en-têtes correspondent aux synonymes canoniques d'un type, ce type est retenu.

Si aucune stratégie ne donne de résultat → `type_detecte: null` (l'utilisateur devra le préciser manuellement).

---

## 7. Phase 2 — Import RBM multi-feuilles (LIVRÉ v2.0)

### 7.1 Architecture

```
MultiSheetImportOrchestrator
  ├── Ordonne les feuilles par cascade RBM (Axes → Tâches)
  ├── Crée un BudgetImport "parent" (type_donnees=multi)
  ├── Pour chaque feuille sélectionnée :
  │     └── Délègue à l'ImportService spécialisé
  │         └── Crée un BudgetImport "enfant" (parent_import_id renseigné)
  │             └── Persiste les erreurs dans budget_import_erreurs
  └── Cumule les statistiques (créées, MAJ, erreurs) sur le parent
```

### 7.2 Services concrets

| Service | Type | Colonnes obligatoires | Référence parente |
|---|---|---|---|
| `AxeImportService` | `axes` | libelle | papa_id (contexte) |
| `ProduitImportService` | `produits` | code_axe, libelle | Axe (par code) |
| `SousProduitImportService` | `sous_produits` | code_produit, libelle | Produit (par code) |
| `ActiviteImportService` | `activites` | code_sous_produit, libelle, date_debut, date_fin | Sous-Produit |
| `TacheImportService` | `taches` | code_activite, libelle | Activité |
| `IndicateurImportService` | `indicateurs` | code_sous_produit, code, libelle, type, categorie, frequence_collecte | Sous-Produit |

Tous étendent `AbstractEntityImportService` qui mutualise :
- Lecture de la feuille (PhpSpreadsheet)
- Canonicalisation des lignes via mapping suggéré/override
- Validation des colonnes obligatoires
- Détection des lignes vides
- Persistance des erreurs structurées (`budget_import_erreurs`)
- Transaction DB avec rollback sur erreur ou dry-run
- Finalisation `BudgetImport` (statut, stats, journal)

### 7.3 Endpoint HTTP

`POST /budget/imports/multi-feuilles` (permission `import_budget`) :

| Champ | Type | Description |
|---|---|---|
| `exercice_id` | int | Exercice budgétaire cible |
| `fichier` | file | XLSX/XLS/CSV ≤ 25 Mo |
| `dry_run` | bool (option) | true = simulation sans persistance |
| `feuilles[i][nom]` | string | Nom de la feuille Excel |
| `feuilles[i][type]` | string | axes/produits/sous_produits/activites/taches/indicateurs |
| `feuilles[i][mapping]` | array (option) | Override du mapping en-tête → champ canonique |

### 7.4 Cascade automatique

L'orchestrateur **réordonne les feuilles** dans l'ordre canonique RBM avant exécution. Si l'utilisateur soumet `[Produits, Axes]`, l'orchestrateur exécutera `Axes` en premier — garantissant que les références parentes existent.

Si une feuille échoue (erreur critique), la cascade s'arrête pour préserver la cohérence référentielle. Les feuilles suivantes ne sont pas traitées.

### 7.5 Mode upsert intelligent

Chaque service :
- Si la ligne contient un `code` qui existe déjà sur le parent (axe/produit/etc.) → **mise à jour** des champs
- Sinon → **création** avec attribution automatique du code via les Observers de codification existants

→ Re-importer le même fichier deux fois ne crée pas de doublons.

## 8. Roadmap (phases restantes)

### Phase 3 — Mapping persisté UI (~3 j)
- UI pour sauvegarder le mapping après une analyse réussie
- Sélecteur de mapping dans le formulaire d'import (pré-remplir depuis un mapping enregistré)

### Phase 4 — Rapport d'erreurs XLSX riche (~3 j)
- Génération XLSX avec couleurs (rouge=erreur, orange=avertissement, jaune=info)
- Liens hypertexte vers les lignes en erreur
- Section "corrections suggérées" automatique

### Phase 5 — Rollback transactionnel (~1 sem)
- Ajout d'une colonne `originated_from_import_id` sur les ressources créées
- Endpoint `POST /budget/imports/{import}/rollback` avec confirmation
- Suppression sélective + remise à `statut='rollback'`

### Phase 6 — Jobs asynchrones (~3 j)
- `BudgetImportJob` (Laravel queue) pour fichiers > 5 000 lignes
- Notification utilisateur quand l'import est terminé

---

## 8. Tests

`tests/Unit/Services/ExcelAnalyzerServiceTest.php` — 6 cas :
- Détection feuille budget par nom
- Détection feuille axes par nom + en-têtes
- Détection multi-feuilles (3 types simultanés)
- Colonnes inconnues listées
- Champs canoniques manquants signalés
- Type non détecté si aucun indice

---

## 9. Compatibilité

| Aspect | Statut |
|---|---|
| **Anciens fichiers mono-feuille** | ✅ Continuent à fonctionner sans changement |
| **Anciens templates Excel** | ✅ Le modèle officiel reste valide |
| **Routes nommées existantes** (`imports.index`, `imports.execute`…) | ✅ Inchangées |
| **Permissions** (`import_budget`, `export_budget`) | ✅ Réutilisées |
| **Historique d'imports antérieurs** | ✅ Préservé, nouveaux champs nullables |
| **BudgetImportService** | ✅ Aucune modification — peut être étendu en Phase 2 |

---

## 10. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **RBM/GAR CEEAC** | Chaîne Axe→Tâche dans le mapping canonique | ✅ |
| **ISO 9001 § 7.5** | Maîtrise des informations documentées | ✅ historique + hash SHA256 |
| **COSO ERM** | Traçabilité des opérations critiques | ✅ user + date + journal |
| **OWASP A04 Insecure Design** | Validation fichier + mime + taille | ✅ |
| **RGPD art. 30** | Registre des traitements | 🟡 partiel (à venir Phase 5) |

---

**Fin du document** — version 1.0 (Phase 1)
