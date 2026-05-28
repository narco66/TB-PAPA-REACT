# Chaîne de la dépense — Documentation technique

**TB-PAPA-CEEAC** · Module institutionnel de gestion complète de la dépense, de l'expression du besoin au paiement.

---

## 1. Vue d'ensemble

Le module Chaîne de la dépense implémente le cycle complet RGCP (Règlement Général de Comptabilité Publique) tel que pratiqué dans les organisations régionales africaines. Il s'appuie sur le moteur IPSAS existant (`BudgetCycleService`) qu'il enrichit en amont (expression du besoin, fournisseurs, multi-imputation) et en aval (service fait, réception, liquidation détaillée).

### Cycle implémenté

```
Étape 1  Expression du besoin
Étape 2  Vérification de la disponibilité budgétaire
Étape 3  Engagement budgétaire
Étape 4  Contrôle et visa financier
Étape 5  Exécution de la prestation
Étape 6  Service fait / Réception
Étape 7  Liquidation détaillée
Étape 8  Ordonnancement
Étape 9  Paiement
Étape 10 Archivage, reporting et audit
```

---

## 2. Référentiels de conformité

| Norme | Domaine couvert |
| --- | --- |
| **RGCP** | Chaîne de la dépense publique (4 étapes : engagement → liquidation → ordonnancement → paiement) |
| **IPSAS 1** | Présentation des états financiers du secteur public |
| **IPSAS 24** | Information budgétaire dans les états financiers |
| **COSO ERM 2017** | Séparation ordonnateur/comptable, contrôle interne |
| **OHADA** | Système comptable Afrique centrale |
| **OWASP Top 10** | Sécurité applicative (validation, audit trail) |
| **RGPD** | Protection des données personnelles, droit à l'oubli |
| **ISO 27001** | Hash SHA-256 + intégrité des documents |

---

## 3. Modèle de données

### 3.1 Tables créées (Phases 1+2)

| Table | Rôle | Champs clés |
| --- | --- | --- |
| `suppliers` | Référentiel fournisseurs | code, libellé, type, NIF, RCCM, compte bancaire |
| `expense_requests` | Expression du besoin | numéro EB-YYYY-NNNNN, statut workflow, montants CEEAC/PTF, type_engagement (18 types) |
| `commitment_lines` | Multi-imputation engagement | budget_mouvement_id, budget_ligne_id, montant, source |
| `service_done_certificates` | Certificat service fait | référence SF-YYYY-NNNNN, montant constaté, conformité qualitative/quantitative |
| `receptions` | PV de réception | référence PV-YYYY-NNNNN, type (provisoire/définitive/partielle/refus), commission 3 membres |
| `liquidation_details` | Détail liquidation | retenue garantie, retenue fiscale, pénalités, montant net calculé |

### 3.2 Tables existantes réutilisées (non dupliquées)

- `budget_exercices`, `budget_lignes`, `budget_mouvements` (cycle IPSAS), `budget_sources_financement`
- `partenaires` (PTF, distinct des suppliers commerciaux)
- `validations`, `documents`, `alertes`, `generated_reports`, `notifications`, `activity_log`

### 3.3 Extension non destructive de `budget_mouvements`

Trois colonnes ajoutées par migration `2026_05_28_010010_create_expense_chain_phase1` :
- `expense_request_id` — Lien vers l'expression source
- `supplier_id` — Fournisseur retenu (distinct du partenaire PTF)
- `type_engagement` — Parmi les 18 types métiers

---

## 4. Workflows

### 4.1 Workflow Expression du besoin

```
brouillon ──► soumis ──► en_validation_hierarchique ──► valide ──► engage
   │            │                  │                       │
   │            └──► retourne_correction (puis brouillon)  │
   │                                                       │
   └────────────────► rejete                               │
                                                            │
                            annule (avant engagement) ◄────┘
```

**Transitions autorisées par `ExpenseRequestService`** :
- `creer()` → brouillon
- `soumettre()` → soumis (depuis brouillon ou retourne_correction)
- `valider()` → valide (depuis soumis/en_validation)
- `retourner()` → retourne_correction (depuis soumis/en_validation)
- `rejeter()` → rejete (depuis n'importe quel statut sauf engage/annule)
- `engager()` → engage + crée BudgetMouvement (depuis valide uniquement)
- `annuler()` → annule (sauf engage)

### 4.2 Workflow Cycle IPSAS (existant, étendu)

```
Engagement ──► Liquidation ──► Ordonnancement ──► Paiement
   │              │                    │                │
   │              └─ ServiceDoneCert + Reception        │
   │                                                    │
   └─ CommitmentLines (multi-imputation)           └─ Mode paiement
      LiquidationDetails (retenues, pénalités)        COSO : comptable ≠ ordonnateur
```

---

## 5. 18 types d'engagements

| Code | Libellé |
| --- | --- |
| `achat_biens` | Achat de biens |
| `prestation_services` | Prestation de services |
| `travaux` | Travaux |
| `mission_officielle` | Mission officielle |
| `formation_atelier` | Formation / atelier / séminaire |
| `contrat_convention` | Contrat ou convention |
| `subvention` | Subvention |
| `appui_institutionnel` | Appui institutionnel |
| `fonctionnement` | Dépenses de fonctionnement |
| `investissement` | Dépenses d'investissement |
| `frais_personnel` | Frais de personnel |
| `regularisation` | Engagement de régularisation |
| `complementaire` | Engagement complémentaire |
| `modificatif` | Engagement modificatif |
| `pluriannuel` | Engagement pluriannuel |
| `financement_ceeac` | Financement CEEAC-EM uniquement |
| `financement_ptf` | Financement PTF uniquement |
| `financement_mixte` | Financement mixte CEEAC/PTF |

---

## 6. Architecture logicielle

### 6.1 Services métier

| Service | Responsabilité |
| --- | --- |
| `ExpenseRequestService` | Workflow expression du besoin + conversion en engagement (utilise `BudgetCycleService`) |
| `ServiceFaitReceptionService` | Certificat SF + PV réception + détail liquidation (retenues/pénalités) |
| `BudgetCycleService` | Moteur IPSAS (engagement/liquidation/ordonnancement/paiement) — existant, étendu |
| `ExpenseNotificationDispatcher` | Routage des notifications par événement + résolution destinataires |
| `DashboardExpenseStatsService` | KPI temps réel chaîne dépense (11 sections) |
| `ExpenseJournalExportService` | Génération Excel/CSV des 6 journaux |

### 6.2 Couche HTTP

| Controller | Routes |
| --- | --- |
| `ExpenseRequestController` | 10 routes : CRUD + workflow (submit/validate/reject/return/engage/cancel) |
| `SupplierController` | 2 routes : index, store |
| `ServiceFaitController` | 5 routes : index + storeCertificat + validate + storeReception + validate |
| `ExpenseDashboardController` | 1 route : `GET /expense` (invokable) |
| `ExpenseExportController` | 1 route : `GET /expense/exports/{journal}.{format}` |

### 6.3 Pages React

- `expense/dashboard.tsx` — Tableau de bord avec KPI + charts Recharts
- `expense/requests/{index,form,show}.tsx` — CRUD expression du besoin
- `expense/suppliers/index.tsx` — Référentiel fournisseurs avec formulaire inline
- `expense/service-fait/index.tsx` — Certificats SF + PV réception

### 6.4 Composants React réutilisables

- `<PdfExportButton reportKey="..." filtres={...} />` — Bouton PDF universel
- `<ExcelExportButton journal="..." filtres={...} />` — Dropdown XLSX/CSV

---

## 7. Sécurité & Permissions (RBAC)

### 7.1 Permissions dédiées

| Permission | Description |
| --- | --- |
| `expense.viewAny` | Liste expressions |
| `expense.view` | Détail expression |
| `expense.create` | Créer expression |
| `expense.update` | Modifier expression brouillon |
| `expense.submit` | Soumettre pour validation |
| `expense.validate_hierarchique` | Valider hiérarchiquement |
| `expense.reject` | Rejeter avec motif |
| `expense.engage` | Convertir en engagement budgétaire |
| `expense.cancel` | Annuler (avant engagement) |
| `supplier.viewAny` | Liste fournisseurs |
| `supplier.manage` | Créer/modifier fournisseur |

### 7.2 Matrice RACI

| Rôle | View | Create | Submit | Validate | Reject | Engage | Manage Suppliers |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `point_focal` | ✓ | ✓ | ✓ | | | | |
| `chef_service` | ✓ | ✓ | ✓ | | | | |
| `directeur_technique` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `directeur_appui` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `commissaire` | ✓ | | | ✓ | ✓ | ✓ | ✓ |
| `secretaire_general` | ✓ | | | ✓ | ✓ | ✓ | ✓ |
| `controle_financier` | ✓ | | | ✓ | | | ✓ |
| `audit_interne` | ✓ | | | | | | ✓ |
| `admin_technique` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

---

## 8. Règles de gestion enforced

1. **Anti-dépassement budgétaire** : engagement refusé si > disponible (`BudgetCycleService::engager`)
2. **Total = CEEAC + PTF** : cohérence validée par `validerCoherenceMontants()` à la création de l'expression
3. **Engagement ⊂ valide** : impossible d'engager une expression non validée
4. **Liquidation ≤ Engagement** : plafond strict imposé par `BudgetCycleService::liquider`
5. **Service fait ≤ Engagement** : plafond imposé sur `montant_constate` du certificat
6. **Séparation ordonnateur ≠ comptable** : violation rejetée par `BudgetCycleService::payer` (COSO ERM)
7. **Chaînage parent_mouvement_id** : engagement → liquidation → ordonnancement → paiement, traçabilité linéaire
8. **Numérotation institutionnelle** : `EB-YYYY-NNNNN`, `SF-YYYY-NNNNN`, `PV-YYYY-NNNNN` auto-générés et uniques
9. **Soft deletes** : aucune perte de données, restauration possible par admin
10. **Activity log** : toutes les actions tracées via Spatie LogsActivity

---

## 9. Notifications événementielles (11 événements)

`ExpenseNotificationDispatcher` route automatiquement les notifications via canal `database` :

| Événement | Déclencheur | Destinataires |
| --- | --- | --- |
| `expression.soumise` | `submit()` | Directeurs, Commissaires, SG |
| `expression.validation_attendue` | (alias soumise) | Directeurs, Commissaires |
| `expression.retour_correction` | `retourner()` | Demandeur uniquement |
| `expression.rejet` | `rejeter()` | Demandeur uniquement |
| `expression.engagee` | `engager()` | Demandeur + Contrôle financier + Valideur |
| `visa.accorde` | `valider()` | Demandeur |
| `service_fait.attendu` | `creerCertificatServiceFait()` | Contrôle financier |
| `liquidation.validee` | `creerDetailLiquidation()` | Contrôle financier |
| `ordonnancement.genere` | `BudgetCycleService::ordonnancer()` | Comptable + CF |
| `paiement.effectue` | `BudgetCycleService::payer()` | Ordonnateur + Demandeur + Valideur |
| `echeance.depassee` | Commande Artisan `expense:detecter-echeances` | Directeurs, Commissaires |

### Anti-auto-notification

Le dispatcher **exclut systématiquement l'auteur** de l'événement (`$auteur->id` retiré de la liste des destinataires). Aucun utilisateur n'est notifié de sa propre action.

### Planification de la détection des retards

À ajouter dans `routes/console.php` :
```php
Schedule::command('expense:detecter-echeances')->dailyAt('07:00');
```

---

## 10. Documents PDF générés (15 templates)

| # | Template | Étape | Filtre |
| --- | --- | --- | --- |
| 1 | `fiche_expression_besoin` | Étape 1 | expense_request_id |
| 2 | `demande_visa_financier` | Étape 4 | expense_request_id |
| 3 | `visa_financier` | Étape 4 | expense_request_id |
| 4 | `notification_retour` | Étape 4 | expense_request_id |
| 5 | `notification_rejet` | Étape 4 | expense_request_id |
| 6 | `bon_engagement` | Étape 3 | mouvement_id |
| 7 | `certificat_service_fait` | Étape 6 | certificat_id |
| 8 | `pv_reception` | Étape 6 | reception_id |
| 9 | `fiche_liquidation` | Étape 7 | mouvement_id |
| 10 | `decompte_paiement` | Étape 7 | mouvement_id |
| 11 | `ordonnance_paiement` | Étape 8 | mouvement_id |
| 12 | `bordereau_ordonnancement` | Étape 8 | date_debut + date_fin |
| 13 | `avis_paiement` | Étape 9 | mouvement_id |
| 14 | `recu_paiement` (quittance) | Étape 9 | mouvement_id |
| 15 | `tableau_bord_expense` | §13 | exercice_id |

### Listings PDF (filtrables)

`liste_expense_requests`, `liste_suppliers`, `liste_engagements`, `liste_liquidations`, `liste_ordonnancements`, `liste_paiements`.

### Caractéristiques institutionnelles

- Logo CEEAC officiel embarqué en base64
- Hash SHA-256 + QR code de vérification (URL `/rapports/verifier/{code}`)
- Signature électronique de l'émetteur
- Métadonnées institutionnelles (date émission, auteur, classification)
- Archivage automatique dans `generated_reports`

---

## 11. Exports Excel/CSV (6 journaux)

| Journal | Endpoint | Volumétrie max |
| --- | --- | --- |
| Expressions du besoin | `GET /expense/exports/expressions.{xlsx\|csv}` | 5 000 |
| Engagements | `GET /expense/exports/engagements.{xlsx\|csv}` | 5 000 |
| Liquidations | `GET /expense/exports/liquidations.{xlsx\|csv}` | 5 000 |
| Ordonnancements | `GET /expense/exports/ordonnancements.{xlsx\|csv}` | 5 000 |
| Paiements | `GET /expense/exports/paiements.{xlsx\|csv}` | 5 000 |
| Fournisseurs | `GET /expense/exports/suppliers.{xlsx\|csv}` | 5 000 |

Filtres transmis en query string : `statut`, `type`, `exercice_id`.

Caractéristiques techniques :
- Encodage UTF-8 avec BOM pour CSV (compatibilité Excel)
- Délimiteur CSV : `;`
- Excel : titre stylé bleu CEEAC, header gras blanc/bleu, freeze pane sur ligne données, auto-width colonnes
- Suppression automatique du fichier temporaire après envoi (`deleteFileAfterSend(true)`)

---

## 12. Tests

### 12.1 Couverture

| Suite | Tests | Assertions |
| --- | --- | --- |
| `ExpenseChainTest` (Phase 1+2) | 11 | 30 |
| `ExpenseDashboardTest` (Phase 3) | 4 | 12 |
| `ExpenseNotificationsTest` (Phase 4) | 7 | 15 |
| `ExpenseExportsTest` (Phase 5) | 7 | 32 |
| `ExpensePdfPhase6Test` (Phase 6) | 11 | 50 |
| **Total Expense** | **40** | **139** |

### 12.2 Lancer les tests

```bash
# Tous les tests Expense
php artisan test tests/Feature/Expense/

# Un suite spécifique
php artisan test tests/Feature/Expense/ExpenseChainTest.php

# Avec coverage
php artisan test tests/Feature/Expense/ --coverage
```

---

## 13. Commandes Artisan

```bash
# Détection automatique des engagements dont l'échéance est dépassée
php artisan expense:detecter-echeances [--dry-run]

# Re-seed permissions après mise à jour du seeder
php artisan db:seed --class=RolesPermissionsSeeder --force

# Migration des tables (idempotent)
php artisan migrate
```

---

## 14. Migrations

```
2026_05_28_010010_create_expense_chain_phase1.php
  - Table suppliers
  - Table expense_requests
  - Table commitment_lines
  - Extension budget_mouvements (3 colonnes : expense_request_id, supplier_id, type_engagement)

2026_05_28_020010_create_expense_chain_phase2.php
  - Table service_done_certificates
  - Table receptions
  - Table liquidation_details
```

Toutes les migrations sont **non destructives** : aucune donnée existante n'est modifiée. Rollback propre via `php artisan migrate:rollback --step=2`.

---

## 15. Étendre le module

### Ajouter un nouveau type d'engagement

1. Modifier l'enum dans `database/migrations/2026_05_28_010010_create_expense_chain_phase1.php` (et créer une nouvelle migration `ALTER`)
2. Ajouter le label dans `ExpenseRequest::TYPES_ENGAGEMENT`
3. Mettre à jour le tableau de bord et les seeders si besoin

### Ajouter un nouveau workflow d'événement

1. Ajouter le label dans `ExpenseEventNotification::EVENT_LABELS`
2. Ajouter une règle dans `ExpenseNotificationDispatcher::resoudreDestinataires()`
3. Appeler `$dispatcher->notifier($event, $sujet, $auteur, ...)` au bon endroit

### Ajouter un nouveau PDF

1. Créer la classe Report dans `app/Reports/Expense/`
2. Créer le template Blade dans `resources/views/reports/expense/`
3. Enregistrer dans `ReportRegistry::$reports[]`
4. Si besoin, ajouter un bouton dans la page React avec `<a href="/rapports/{key}/quick?..." />`

---

## 16. Limites connues / Améliorations futures

- **Multi-imputation à la création** : implémentée mais saisie React simplifiée (IDs numériques) → améliorer avec un picker ligne budgétaire
- **Workflow rules engine** : transitions codées en dur dans `ExpenseRequestService` → envisager un état-machine déclaratif (`spatie/laravel-model-states`)
- **Notifications par mail** : actuellement `database` uniquement → ajouter `mail` quand SMTP institutionnel configuré
- **Tests E2E** : couverts par tests Feature HTTP, mais Cypress/Playwright pour parcours UI complet à prévoir
- **Pièces jointes documentaires** : utiliser le système GED existant (`documents` polymorphique) — déjà possible mais non câblé dans le UI

---

## 17. Fichiers livrés

```
app/
├── Models/
│   ├── Supplier.php
│   ├── ExpenseRequest.php
│   ├── CommitmentLine.php
│   ├── ServiceDoneCertificate.php
│   ├── Reception.php
│   └── LiquidationDetail.php
├── Services/Expense/
│   ├── ExpenseRequestService.php
│   ├── ServiceFaitReceptionService.php
│   ├── ExpenseNotificationDispatcher.php
│   ├── DashboardExpenseStatsService.php
│   └── ExpenseJournalExportService.php
├── Http/
│   ├── Controllers/Expense/
│   │   ├── ExpenseDashboardController.php
│   │   ├── ExpenseRequestController.php
│   │   ├── SupplierController.php
│   │   ├── ServiceFaitController.php
│   │   └── ExpenseExportController.php
│   └── Requests/Expense/
│       └── StoreExpenseRequestRequest.php
├── Notifications/
│   └── ExpenseEventNotification.php
├── Console/Commands/
│   └── DetecterEcheancesExpenseCommand.php
└── Reports/Expense/
    ├── (15 classes Report)

database/migrations/
├── 2026_05_28_010010_create_expense_chain_phase1.php
└── 2026_05_28_020010_create_expense_chain_phase2.php

resources/
├── js/pages/expense/
│   ├── dashboard.tsx
│   ├── requests/{index,form,show}.tsx
│   ├── suppliers/index.tsx
│   └── service-fait/index.tsx
├── js/components/common/
│   ├── pdf-export-button.tsx
│   └── excel-export-button.tsx
└── views/reports/expense/
    └── (15 templates Blade)

tests/Feature/Expense/
├── ExpenseChainTest.php
├── ExpenseDashboardTest.php
├── ExpenseNotificationsTest.php
├── ExpenseExportsTest.php
└── ExpensePdfPhase6Test.php

docs/
├── CHAINE-DEPENSE-TECHNIQUE.md (ce document)
└── CHAINE-DEPENSE-GUIDE-UTILISATEUR.md
```

---

**Document maintenu par la DSI CEEAC** · Dernière mise à jour : Phase 7 du chantier Chaîne de la dépense.
