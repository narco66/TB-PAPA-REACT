# Données de démonstration TB-PAPA-CEEAC

## Commande

```bash
php artisan tbpapa:seed-demo --fresh --year=2026 --with-audit --with-imports --with-notifications --with-kpi-history
```

Options :

- `--fresh` : supprime uniquement les données marquées DEMO puis régénère.
- `--append` : ajoute ou complète les données sans supprimer l’existant.
- `--rollback` : supprime uniquement les données DEMO.
- `--year=2026` : année centrale de génération.
- `--with-audit` : ajoute plans, missions, constats et recommandations d’audit.
- `--with-imports` : ajoute imports Excel simulés, mappings et erreurs.
- `--with-notifications` : ajoute notifications utilisateur.
- `--with-kpi-history` : ajoute rapports et historiques KPI.

En production, la commande exige une confirmation interactive.

## Comptes

Les comptes de démonstration sont :

- `demo.user01@ceeac.int` à `demo.user30@ceeac.int`
- Mot de passe : `Password@2026`

## Stratégie

Le seeder ne supprime pas les données existantes. Les données générées utilisent :

- emails `demo.*@ceeac.int` ;
- codes préfixés `DEMO-` ;
- observations contenant `DEMO` ;
- fichiers d’import `DEMO-*`.

Cela permet un rollback ciblé sans toucher aux données opérationnelles.

## Volumes générés

Le jeu de démonstration couvre au minimum :

- 7 départements DEMO ;
- 20 services/directions DEMO ;
- 5 exercices budgétaires ;
- 10 axes ;
- 30 produits ;
- 60 sous-produits ;
- 150 activités ;
- 500 tâches ;
- 100 lignes budgétaires ;
- 80 indicateurs ou plus ;
- 30 utilisateurs ;
- imports, erreurs, mappings, rapports, validations, alertes et audit.

## Contrôles

Les données respectent :

- `Axe -> Produit -> Sous-Produit -> Activité -> Tâche` ;
- `Chapitre -> Article -> Paragraphe -> Ligne budgétaire` ;
- `Total = Part CEEAC + Part PTF` ;
- rattachements utilisateurs, départements, sources et exercices ;
- statuts variés pour dashboards et workflows.

## Rollback

```bash
php artisan tbpapa:seed-demo --rollback
```

Le rollback est limité aux données portant les marqueurs DEMO.
