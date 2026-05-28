# Audit ERP du module budget et import Excel

## Cartographie technique

- Budget : `BudgetExercice`, `BudgetLigne`, `BudgetMouvement`, `BudgetSourceFinancement`.
- Nomenclature ajoutée : `budget_chapitres`, `budget_articles`, `budget_paragraphes`.
- Import : `BudgetImportExportController`, `BudgetImportService`, `ExcelAnalyzerService`, `MultiSheetImportOrchestrator`.
- Traçabilité : `budget_imports`, `budget_import_erreurs`, `budget_import_mappings`, `originated_from_import_id`.
- Cycle d'exécution : `BudgetCycleService` couvre engagement, liquidation, ordonnancement et paiement avec séparation ordonnateur/comptable.
- Reporting : exports Excel/PDF budget consolidé et lignes détaillées.

## Cartographie métier

La ligne budgétaire institutionnelle est désormais structurée ainsi :

`Chapitre -> Article -> Paragraphe -> Ligne budgétaire`

Exemple `60111` :

- Chapitre : `60`
- Article : `601`
- Paragraphe : `6011`
- Ligne budgétaire : `60111`

Le rattachement RBM/GAR reste disponible en parallèle :

`Axe -> Produit -> Sous-Produit -> Activité -> Tâche`

## Ecarts identifiés

| Domaine | Ecart initial | Risque | Correction |
| --- | --- | --- | --- |
| Nomenclature | Chapitre/article/paragraphe stockés comme codes libres | Orphelins, collisions, faiblesse 3NF | Tables dédiées + FK nullable |
| Import | Code budgétaire non reconstruit automatiquement | Imputations incohérentes | `BudgetNomenclatureService` |
| Contrôle financier | Contrôle présent en saisie, pas centralisé | Divergence import/saisie | Contrôle central `Total = CEEAC + PTF` |
| Auditabilité | Import historisé mais nomenclature non versionnée | Traçabilité partielle | `version`, `validated_by`, `validated_at` sur lignes |
| ERP | Référentiel budgétaire non normalisé | Consolidation fragile | Normalisation 3NF non destructive |

## Alignement standards

- IPSAS/SYSCOHADA : séparation prévision, réalisation, exécution et sources.
- COSO/INTOSAI : séparation des tâches et contrôle anti-dépassement dans le cycle.
- ISO 27001/COBIT : RBAC, validation fichier, stockage privé, hash import, journalisation.
- ISO 9001/ITIL : workflow répétable, rapports d'erreurs, reprise et rollback.
- RBM/GAR : rattachement des budgets aux entités de résultats.

## Corrections appliquées

- Création non destructive des tables :
  - `budget_chapitres`
  - `budget_articles`
  - `budget_paragraphes`
- Extension non destructive de `budget_lignes` :
  - `budget_chapitre_id`
  - `budget_article_id`
  - `budget_paragraphe_id`
  - `budget_ligne_code`
  - `devise`
  - `validated_by`
  - `validated_at`
  - `version`
- Service métier `BudgetNomenclatureService` :
  - découpe automatique des codes ;
  - création contrôlée des référentiels ;
  - contrôle de collision hiérarchique ;
  - contrôle `Total = Part CEEAC + Part PTF`.
- Intégration dans :
  - saisie manuelle des lignes ;
  - import Excel officiel ;
  - import feuille `Budget` Malabo.

## Recommandations restantes

Priorité 1 :

- Ajouter une table `budget_versions` pour snapshots complets par exercice.
- Ajouter une table `budget_validations` pour workflow formel de validation hiérarchique.
- Ajouter un verrou applicatif par exercice pendant l'import définitif.

Priorité 2 :

- Déplacer les imports volumineux vers queue jobs avec chunk reading.
- Ajouter rapports PDF d'audit par import.
- Ajouter tableaux de bord drill-down par chapitre/article/paragraphe.

Priorité 3 :

- Ajouter signatures électroniques institutionnelles.
- Ajouter scan antivirus côté stockage si l'infrastructure le permet.
- Ajouter monitoring technique des imports longs.

## Plan de rollback

Les changements sont non destructifs. En cas de problème :

1. Désactiver l'usage des nouveaux champs côté service.
2. Les lignes existantes restent lisibles via les anciens champs `chapitre_code`, `article_code`, `paragraphe_code`, `code_action`.
3. Les imports créés restent annulables via `originated_from_import_id`.
4. La migration peut être annulée tant qu'aucune dépendance métier externe ne consomme les nouvelles tables.
