# Audit fonctionnel — Reporting institutionnel TB-PAPA-CEEAC

**Date** : 2026-05-27 · **Version** : 1.0

## Synthèse

Audit complet du dispositif de reporting existant et identification des productions documentaires à mettre en place pour transformer TB-PAPA-CEEAC en plateforme institutionnelle de pilotage stratégique conforme RBM/GAR, COSO, IPSAS et standards de gouvernance communautaire.

---

## 1. État existant

### 1.1 Modules opérationnels (livrables)

| Module | État | Productions actuelles |
|---|---|---|
| **PAPA** (annuel) | ✅ Complet | Index + show, workflow validation, soft-delete |
| **Chaîne RBM** : Axe → Produit → SP → Activité → Tâche | ✅ Complet | CRUD + codification auto + recalcul d'avancement |
| **Indicateurs (KPI)** | ✅ Complet | Saisie périodique + valeurs historisées |
| **Activités + Gantt** | ✅ Complet | Diagramme de Gantt SVG natif |
| **Budget institutionnel** | ✅ Complet | Exercices, lignes hiérarchiques, mouvements, dashboard, import/export Excel |
| **GED Documents** | ✅ Complet | Upload, validation, hash SHA-256 |
| **Alertes & Risques** | ✅ Complet | Détection auto (retards/dérives/sous-performance) |
| **Admin Users + Audit** | ✅ Complet | RBAC Spatie + Activity Log |

### 1.2 Vues analytiques existantes

- Dashboard exécutif (compteurs RBM 5 niveaux + budget agrégé)
- Dashboard budget (CEEAC-EM vs PTF, exécution, écarts)
- Gantt dynamique avec ligne du jour
- Vues filtrables par axe / produit / sous-produit / activité

### 1.3 Exports existants

- Excel : import/export budget (consolidé, par Axe, par source, comparatif)
- PDF : aucun (à construire)

---

## 2. Lacunes identifiées

### 2.1 Lacunes documentaires

❌ **Aucun PDF institutionnel généré** depuis l'application
❌ Pas de **page de génération** centralisée
❌ Pas d'**historique de rapports** produits
❌ Pas de **planification** de rapports récurrents
❌ Pas de **QR Code de vérification** pour les documents officiels
❌ Pas de **signature numérique** ni de **filigrane** institutionnel

### 2.2 Lacunes analytiques

❌ Pas de matrice **RBM consolidée** (Axe→Produit→SP→Activité→Tâche en un coup d'œil)
❌ Pas de matrice **RACI** exportable
❌ Pas de matrice **des risques** consolidée
❌ Pas de **rapport comparatif inter-annuel** PAPA N vs N-1
❌ Pas de **fiches synthétiques** par Axe/Produit/SP pour les Commissaires

### 2.3 Lacunes de redevabilité

❌ Pas de **rapport bailleur** automatique (CEEAC vs PTF avec attribution par axe)
❌ Pas de **synthèse exécutive** pour le Cabinet Présidentiel
❌ Pas de **PV de validation** archivable en GED
❌ Pas de **bordereau d'archivage** des documents validés

---

## 3. Architecture proposée

### 3.1 Moteur PDF central

```
┌────────────────────────────────────────────────────┐
│        Report (abstract class)                      │
│  - key, title, description, category                │
│  - filters(): array de FilterDefinition             │
│  - data(filters): array                             │
│  - template(): string (Blade)                       │
│  - orientation(): 'portrait'|'landscape'            │
└──────────────────┬─────────────────────────────────┘
                   │
   ┌───────────────┼─────────────────────────────┐
   │               │                             │
   ▼               ▼                             ▼
StrategiquePapaReport   BudgetConsolideReport   ...
   │               │                             │
   └──────────────►ReportRegistry◄───────────────┘
                        │
                        ▼
               PdfGeneratorService
                  │      │      │
                  ▼      ▼      ▼
             Blade    DomPDF   QR Code
             Template Render   Verification
                        │
                        ▼
                Storage + GeneratedReport DB
```

### 3.2 Familles de rapports livrées en V1

| # | Catégorie | Rapport | Statut |
|---|---|---|---|
| 1 | **Stratégique** | Plan d'Action Prioritaire Annuel (PAPA complet) | ✅ |
| 2 | **Stratégique** | Synthèse exécutive Présidence | ✅ |
| 3 | **Budgétaire** | Budget consolidé annuel | ✅ |
| 4 | **Performance** | Fiche performance par Axe | ✅ |
| 5 | **RBM/GAR** | Matrice RBM consolidée (5 niveaux) | ✅ |
| 6 | **Gouvernance** | Matrice RACI | ✅ |
| 7 | **Audit** | Journal d'audit institutionnel | ✅ |
| 8 | **Opérationnel** | Tableau de bord exécutif PDF | ✅ |

### 3.3 Familles à étendre (architecture prête, à instancier)

Le moteur central rend trivial l'ajout de nouveaux rapports : il suffit d'ajouter une classe héritant de `Report` et de l'enregistrer dans `ReportRegistry`. Familles prévues :

- **Stratégiques** : Fiches par Produit / Sous-Produit, Cadre logique, Matrice risques
- **Budgétaires** : Par pilier, par source, par département, écarts, reliquats
- **Suivi** : Mensuel/Trimestriel/Semestriel/Annuel, activités critiques, retards
- **Gouvernance** : PV validation, rapport supervision, conformité
- **Audit** : Anomalies, dépassements, historique modifications
- **Analytique** : Comparatif inter-annuel, tendances, maturité
- **Opérationnel** : Plan de travail, calendrier, fiches activité/tâche
- **PTF** : Rapports bailleur, redevabilité, Chefs d'État

### 3.4 Sécurité & traçabilité

Chaque rapport produit :
- Code de vérification unique encodé en **QR Code**
- Hash **SHA-256** du fichier généré
- Historisation complète dans `generated_reports`
- Compteur de téléchargements + dernier consommateur
- Archivage automatique optionnel vers la GED
- Filigrane "CONFIDENTIEL" pour les documents sensibles

### 3.5 Conformité

| Standard | Application |
|---|---|
| **RBM/GAR** (OCDE/UNDG) | Cadre logique, chaîne de résultats, indicateurs SMART |
| **COSO ERM** | Matrice des risques (niveau, catégorie, traitement) |
| **IPSAS** | Distinction engagement/liquidation/ordonnancement/paiement |
| **ISO 27001** | Audit log immuable, hash, code de vérification |
| **COBIT 2019** | Séparation des rôles, RACI, validation hiérarchique |

---

## 4. Permissions ajoutées

| Permission | Rôles ayant accès |
|---|---|
| `generate_reports` | Président, VP, Commissaire, SG, Directeur, Audit |
| `export_reports` | Tous les rôles avec accès lecture |
| `validate_reports` | Président, VP, Commissaire, SG |
| `archive_reports` | SG, Admin |
| `download_reports` | Tous les rôles avec accès lecture |
| `manage_templates` | Admin technique |
| `manage_report_settings` | Admin technique, SG |
| `schedule_reports` | SG, Admin |

---

## 5. Roadmap V2 (post-livraison V1)

1. **Génération asynchrone** via Laravel Queues (gros rapports > 5 Mo)
2. **Envoi automatique** par email post-validation PAPA
3. **Planification cron** des rapports périodiques (`scheduled_reports`)
4. **Signatures électroniques** PKI / e-IDAS
5. **Comparatif inter-annuel** PAPA N / N-1 / N-2
6. **Tendances et prévisions** (régression linéaire sur indicateurs)
7. **Export Word (.docx)** via PHPWord
8. **Dashboards interactifs** PDF via Chart.js render server-side
