# Restructuration institutionnelle CEEAC

## Commande

```bash
php artisan tbpapa:restructure-ceeac --migrate --year=2026
```

En production, ajouter explicitement `--force`.

## Effets

La commande :

- cree les tables `services` et `organizational_units` si necessaire ;
- sauvegarde les tables applicatives dans `storage/app/backups/ceeac-restructuring/YYYYMMDD-HHMMSS` ;
- supprime les anciennes donnees de demonstration et les anciens rattachements operationnels ;
- conserve les migrations, roles, permissions et journaux `activity_log` ;
- reconstruit l organigramme officiel de la Commission de la CEEAC ;
- regenere utilisateurs, RBM/GAR, budgets 2025-2030, chaine de depense, GED, audit, rapports, imports et notifications ;
- produit un rapport de coherence dans `storage/app/reports/restructuring/ceeac-restructuring-YYYY.json`.

## Compte demo

- Email : `admin@ceeac.org`
- Mot de passe : `Password@2026`

## Controle attendu

Le rapport de coherence doit afficher :

- `orphan_directions` egal a `0` ;
- `budget_incoherent` egal a `0` ;
- des donnees presentes pour departements, directions, services, RBM, budget, GED, audit et reporting.

## Verification rapide

```bash
php artisan about
php artisan route:list --except-vendor
php artisan test tests/Feature/RbmCodificationTest.php tests/Feature/Budget/BudgetNomenclatureServiceTest.php
```
