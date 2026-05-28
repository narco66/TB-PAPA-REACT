# Import budgétaire Excel Malabo 2026

## Audit du module existant

Le module d'import budgétaire repose sur :

- `BudgetImportExportController` : routes d'analyse, import, mapping, rollback et rapports.
- `BudgetImportService` : import budget mono-feuille et validation métier.
- `ExcelAnalyzerService` : analyse multi-feuilles sans écriture.
- `MultiSheetImportOrchestrator` : import RBM en cascade.
- `budget_imports`, `budget_import_mappings`, `budget_import_erreurs` : traçabilité, mappings et erreurs structurées.
- `budget_lignes` : lignes budgétaires rattachables à `axes`, `produits`, `sous_produits`, `activites`, `taches`.

Le fichier `BUDGET de 2026 Final validé à Malabo.xls` est représenté dans `storage/app/private/budget-imports` par un classeur `.xls` contenant notamment `Budget`, `PAP`, `Contributions`, `RésuméPAP2026` et `Synthèse des données`.

## Ecarts corrigés

- La feuille `Budget` n'a pas ses en-têtes en première ligne : titre en ligne 1, en-têtes combinés en lignes 3-4, données à partir de la ligne 5.
- Les montants utilisent des séparateurs de milliers avec virgules, par exemple `14,030,081,000`.
- Plusieurs cellules contiennent des formules Excel en erreur (`#REF!`) ou des lignes décoratives.
- Les colonnes `Budget 2025`, `Recettes réalisées`, `Taux de réalisation`, `Prévisions 2026` et `Variation` doivent être conservées dans `budget_lignes`.

## Fonctionnement

1. Téléverser le fichier depuis `/budget/imports`.
2. Cliquer sur `Analyser`.
3. Vérifier la feuille `Budget` détectée et son mapping.
4. Lancer d'abord une prévisualisation.
5. Lancer l'import définitif uniquement si les contrôles sont acceptables.

L'import définitif est transactionnel. Les doublons existants sont rejetés, aucune ligne existante n'est écrasée sans logique explicite de mise à jour.

## Modèle officiel

Le modèle `/budget/imports/modele` génère un classeur global multi-feuilles :

- `Instructions`
- `Référentiels`
- `Budget à importer`
- `Budget`
- `PAP`
- `Contributions`
- `Indicateurs`
- `Responsables`

## Rapports et reprise

- CSV : `/budget/imports/{import}/rapport-erreurs`
- XLSX coloré : `/budget/imports/{import}/rapport-erreurs.xlsx`
- Rollback : `/budget/imports/{import}/rollback`, réservé aux administrateurs techniques.
