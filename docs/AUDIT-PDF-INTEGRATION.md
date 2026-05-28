# Audit d'intégration PDF dans les pages — TB-PAPA-CEEAC

**Date** : 2026-05-27 · **Version** : 1.0

## 1. État actuel du dispositif PDF

Le moteur PDF central est opérationnel ([AUDIT-REPORTING.md](AUDIT-REPORTING.md)) avec 9 rapports concrets :
PAPA stratégique, Synthèse exécutive, Budget consolidé, Performance Axe, Matrice RBM, Matrice indicateurs, Matrice RACI, Journal d'audit, Tableau de bord exécutif.

**Limite identifiée** : ces rapports ne sont accessibles qu'à travers `/rapports`. Les pages métier (PAPA show, Axe show, etc.) n'offrent **aucun export PDF contextuel**.

## 2. Pages cibles à équiper

| # | Page | Rapport contextuel | Paramètres auto |
|---|---|---|---|
| 1 | `/dashboard` | Tableau de bord exécutif | — |
| 2 | `/papa/{id}` | PAPA stratégique + Synthèse exécutive | papa_id |
| 3 | `/rbm/axes/{id}` | Fiche Axe + Performance Axe | axe_id |
| 4 | `/rbm/produits/{id}` | Fiche Produit *(nouveau)* | produit_id |
| 5 | `/rbm/sous-produits/{id}` | Fiche Sous-Produit *(nouveau)* | sp_id |
| 6 | `/activites/{id}` | Fiche Activité *(nouveau)* | activite_id |
| 7 | `/rbm/taches/{id}` | Fiche Tâche *(nouveau)* | tache_id |
| 8 | `/budget/dashboard` | Budget consolidé | exercice_id |
| 9 | `/admin/departements/{id}` | Fiche Département *(nouveau)* | departement_id |
| 10 | `/admin/audit` | Journal d'audit | — |

## 3. Rapports manquants à créer (5)

1. **FicheProduitReport** — détail d'un produit avec sous-produits + activités
2. **FicheSousProduitReport** — détail d'un sous-produit avec activités + indicateurs
3. **FicheActiviteReport** — détail d'une activité avec tâches + Gantt mini
4. **FicheTacheReport** — détail d'une tâche avec assignation + délais
5. **FicheDepartementReport** — fiche département avec commissaire + directions + axes

## 4. Solution d'intégration

- **Route** `/rapports/{key}/quick?param1=x` qui génère **et redirige immédiatement** vers le téléchargement (pas de formulaire intermédiaire pour les exports contextuels).
- **Composant React `PdfQuickButton`** réutilisable :
  ```tsx
  <PdfQuickButton reportKey="fiche_axe" params={{axe_id: axe.id}}>
    Exporter en PDF
  </PdfQuickButton>
  ```
- Le bouton ouvre la route quick dans un nouvel onglet — UX standard institutionnelle (Banque Mondiale, AfDB).

## 5. Workflow utilisateur cible

```
Page show Axe AXE 1
   │
   ├─[bouton PDF "Fiche détaillée"]
   │      ↓
   │   /rapports/fiche_axe/quick?axe_id=1
   │      ↓
   │   ReportController::quick()
   │      ↓
   │   PdfGenerator::generer + GeneratedReport DB
   │      ↓
   │   redirect → /rapports/{id}/telecharger
   │      ↓
   │   PDF téléchargé instantanément
   │
   └─[bouton PDF "Performance"] → idem fiche_performance_axe
```

Tout cela tracé en `generated_reports` avec hash SHA-256 + QR code de vérification.

## 6. Permissions

Réutilise `generate_reports` et `download_reports` (déjà attribuées à Président, VP, Commissaire, SG, Directeurs, Audit).

## 7. Livrables

- 5 nouveaux rapports + templates Blade
- 1 route `quick` + méthode controller
- 1 composant React `PdfQuickButton`
- 8 intégrations dans pages show
- Tests + smoke
