# Cycle budgétaire IPSAS — TB-PAPA-CEEAC

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre le constat C-003 (Cycle engagement→paiement absent) de [docs/AUDIT-COMPLET.md](AUDIT-COMPLET.md) — DERNIER P0 BLOQUANT PRODUCTION.**

---

## 1. Vue d'ensemble

Le cycle budgétaire IPSAS encadre l'exécution comptable d'une ligne budgétaire en **4 étapes obligatoires en cascade** :

```
Engagement  →  Liquidation  →  Ordonnancement  →  Paiement
(réserve)      (service fait)   (titre)            (décaissement)
```

Conforme aux normes :
- **IPSAS 24** — Présentation de l'information budgétaire dans les états financiers
- **IPSAS 1** — Présentation des états financiers
- **COSO ERM 2017** — Contrôle interne, séparation des tâches
- **CDC § 6.4** — Exécution budgétaire CEEAC

---

## 2. Les 4 étapes du cycle

### 2.1 Engagement
**Définition** : réservation budgétaire en faveur d'un bénéficiaire (bon de commande, marché signé, contrat de prestation).

| Acteur | Ordonnateur (directeur, commissaire, SG) |
|---|---|
| Permission | `engager_budget` |
| Contrôle préalable | **Disponible budgétaire ≥ montant** (anti-dépassement) |
| Effet sur la ligne | `montant_engage += montant` ; `montant_disponible -= montant` |
| Pièce justificative | Bon de commande / contrat / convention |

### 2.2 Liquidation
**Définition** : constatation du service fait (livraison reçue, prestation effectuée).

| Acteur | Valideur (chef de service, directeur, contrôle financier) |
|---|---|
| Permission | `liquider_budget` |
| Contrôle | `montant_liquidation ≤ montant_engagement` (cumulé) |
| Effet sur la ligne | `montant_liquide += montant` |
| Pièce justificative | Bon de livraison / PV de réception / facture |

### 2.3 Ordonnancement
**Définition** : émission du titre de paiement par l'ordonnateur.

| Acteur | Ordonnateur (directeur, commissaire, SG) |
|---|---|
| Permission | `ordonnancer_budget` |
| Contrôle | Une seule ordonnance par liquidation |
| Effet sur la ligne | `montant_ordonnance += montant` |
| Pièce justificative | Titre de paiement / mandat |

### 2.4 Paiement
**Définition** : décaissement effectif par le comptable assignataire.

| Acteur | Comptable (contrôle financier) — **doit être ≠ ordonnateur** (COSO) |
|---|---|
| Permission | `payer_budget` |
| Contrôle | Séparation ordonnateur/comptable + un seul paiement par ordonnancement |
| Effet sur la ligne | `montant_paye += montant` ; `taux_consommation` mis à jour |
| Pièce justificative | Référence virement / numéro chèque / reçu |
| Modes | virement / cheque / especes / mobile_money / autre |

---

## 3. Architecture technique

### 3.1 Modèle de données

Tout le cycle s'appuie sur une **seule table** `budget_mouvements` enrichie (approche non destructive — la table existait déjà).

Colonnes clés :
- `type` enum (engagement/liquidation/ordonnancement/paiement/desengagement/ajustement/transfert)
- `parent_mouvement_id` (FK self) — **chaînage du cycle**
- `ligne_id` (FK budget_lignes)
- `montant` decimal(20,2)
- `numero_piece` (référence comptable)
- `beneficiaire_nom`, `beneficiaire_reference`, `partenaire_id`
- `ordonnateur_id`, `comptable_id` (séparation COSO)
- `mode_paiement` enum (virement/cheque/especes/mobile_money/autre)
- `compte_bancaire`, `date_valeur`
- `statut_mouvement` enum (en_attente/valide/rejete/annule)
- `piece_justificative` text (références GED)

### 3.2 Service `BudgetCycleService`

[app/Services/Budget/BudgetCycleService.php](app/Services/Budget/BudgetCycleService.php) — 4 méthodes publiques :

| Méthode | Vérifications | Effets |
|---|---|---|
| `engager(ligne, montant, ordonnateur, ...)` | montant > 0, disponible ≥ montant | crée mouvement + rafraichirSoldes |
| `liquider(engagement, montant, valideur, ...)` | type parent = engagement, cumul ≤ engagement | crée mouvement chaîné |
| `ordonnancer(liquidation, ordonnateur, ...)` | type parent = liquidation, pas déjà ordonnancé | crée mouvement chaîné |
| `payer(ordonnancement, comptable, mode, ...)` | type parent = ordonnancement, **comptable ≠ ordonnateur**, pas déjà payé | crée mouvement chaîné |

Toutes les méthodes sont en `DB::transaction()` + recalculent automatiquement les 6 soldes sur `budget_lignes`.

### 3.3 Recalcul des soldes

`rafraichirSoldes(ligne)` exécute une requête agrégée (CASE WHEN par type) sur les mouvements `statut_mouvement='valide'`, puis :
- `montant_engage`, `montant_liquide`, `montant_ordonnance`, `montant_paye` ← totaux par type
- `montant_disponible` = `montant_total` - `montant_engage`
- `taux_consommation` = `montant_paye` / `montant_total` × 100

---

## 4. API HTTP

| Méthode | URL | Permission | Form Request |
|---|---|---|---|
| POST | `/budget/lignes/{ligne}/engager` | `engager_budget` | EngagerRequest |
| POST | `/budget/mouvements/{mouvement}/liquider` | `liquider_budget` | LiquiderRequest |
| POST | `/budget/mouvements/{mouvement}/ordonnancer` | `ordonnancer_budget` | OrdonnancerRequest |
| POST | `/budget/mouvements/{mouvement}/payer` | `payer_budget` | PayerRequest |

Toutes les routes retournent `back()` avec `success` ou `errors.cycle` (compatibilité Inertia flash).

### 4.1 Form Request `EngagerRequest`

```
montant                : required|numeric|min:0.01
beneficiaire_nom       : nullable|string|max:191
beneficiaire_reference : nullable|string|max:64
partenaire_id          : nullable|integer|exists:partenaires,id
numero_piece           : nullable|string|max:64
motif                  : nullable|string|max:2000
piece_justificative    : nullable|string|max:1000
```

### 4.2 Form Request `PayerRequest`

```
mode_paiement   : required|in:virement,cheque,especes,mobile_money,autre
numero_piece    : nullable|string|max:64
compte_bancaire : nullable|string|max:64
date_valeur     : nullable|date
motif           : nullable|string|max:2000
```

---

## 5. Permissions par rôle (RACI institutionnel)

| Permission | Rôles habilités |
|---|---|
| `engager_budget` | directeur_technique, admin_technique |
| `liquider_budget` | controle_financier, admin_technique |
| `ordonnancer_budget` | directeur_technique, admin_technique |
| `payer_budget` | controle_financier, admin_technique |

**Note COSO** : `directeur_technique` et `controle_financier` sont des rôles distincts → la séparation ordonnateur/comptable est garantie par le RBAC ET par la vérification métier dans `BudgetCycleService::payer()`.

---

## 6. Contrôles institutionnels imposés

| Contrôle | Mécanisme |
|---|---|
| **Anti-dépassement** | `disponiblePour()` vérifié AVANT `engager()` — exception RuntimeException si dépassement |
| **Liquidation ≤ engagement** | Vérification cumul des liquidations validées d'un engagement |
| **Ordonnancement unique** | 1 ordonnancement par liquidation (exception si déjà émis) |
| **Paiement unique** | 1 paiement par ordonnancement (exception si déjà payé) |
| **Séparation des tâches** | `ordonnateur.id !== comptable.id` au paiement (RuntimeException COSO) |
| **Statut validé** | Seuls les mouvements `statut_mouvement='valide'` impactent les soldes |
| **Transaction atomique** | Chaque opération en `DB::transaction()` |
| **Audit trail** | Spatie ActivityLog active sur BudgetMouvement |

---

## 7. Tests

[tests/Feature/Budget/BudgetCycleIpsasTest.php](tests/Feature/Budget/BudgetCycleIpsasTest.php) — 10 cas :

| # | Test |
|---|---|
| 1 | Cycle complet engagement → liquidation → ordonnancement → paiement (soldes vérifiés) |
| 2 | Engagement refusé si disponible insuffisant |
| 3 | Liquidation ne peut pas dépasser l'engagement |
| 4 | Séparation ordonnateur/comptable imposée au paiement (COSO) |
| 5 | Ordonnancement unique par liquidation |
| 6 | Paiement unique par ordonnancement |
| 7 | Chaîne `parent_mouvement_id` relie correctement les 4 étapes |
| 8 | Endpoint HTTP `POST /lignes/{ligne}/engager` fonctionne |
| 9 | Endpoint refusé sans permission (point_focal) |
| 10 | `disponiblePour()` décroît après chaque engagement |

---

## 8. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **IPSAS 1** | Présentation des états financiers | ✅ 6 colonnes soldes sur budget_lignes |
| **IPSAS 24** | Information budgétaire dans les états financiers | ✅ exécution tracée en 4 étapes |
| **COSO ERM 2017** | Séparation ordonnateur/comptable | ✅ vérification métier + RBAC |
| **CDC § 6.4** | Exécution budgétaire CEEAC | ✅ engagement → paiement |
| **OWASP A04 Insecure Design** | Contrôles métier (anti-dépassement) | ✅ disponiblePour() pré-engagement |
| **ISO 9001 § 8.5** | Maîtrise des changements | ✅ Spatie ActivityLog actif |

---

## 9. Capacités fonctionnelles immédiates

- ✅ Cycle complet engagement → paiement
- ✅ Chaînage parent_mouvement_id pour audit trail
- ✅ Contrôle disponible budgétaire avant engagement
- ✅ Séparation ordonnateur/comptable (vérifications métier)
- ✅ Recalcul automatique des 6 soldes (engagé/liquidé/ordonnancé/payé/disponible/taux)
- ✅ Tracé bénéficiaire (nom, référence NIF, partenaire)
- ✅ Numéros de pièces comptables (bon, titre, mandat, virement)
- ✅ Mode de paiement (virement/chèque/espèces/mobile money/autre)
- ✅ Référence bancaire (compte, date de valeur)
- ✅ Pièces justificatives liées (GED, références externes)
- ✅ Statuts (en_attente/valide/rejete/annule)
- ✅ Spatie ActivityLog (audit complet)
- ✅ Permissions RBAC granulaires (4 permissions distinctes)

---

## 10. Chantiers d'extension futurs (V2+)

| Priorité | Action | Effort |
|---|---|---|
| P2 | Engagements pluriannuels (AE/CP, autorisation d'engagement / crédits de paiement) | M |
| P2 | Virements budgétaires entre lignes (avec workflow d'approbation) | M |
| P2 | Désengagement partiel ou total avec motif | S |
| P2 | Cycle clôture exercice : reports d'engagements non liquidés | M |
| P2 | Pièces justificatives stockées en GED (upload + signature) | M |
| P2 | Trésorerie : suivi cash flow institutionnel | L |
| P3 | Conformité comptable double partie (débit/crédit) | L |
| P3 | Intégration banque (API SWIFT, génération PRB virement) | L |
| P3 | Signature électronique qualifiée eIDAS sur titres | L |
| P3 | UI Kanban du cycle (tableau drag&drop par étape) | M |

---

**Fin du document** — version 1.0 (Phase 1 IPSAS)
