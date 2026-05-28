# Données de démonstration TB-PAPA-CEEAC

Jeu de données institutionnel complet et **rollbackable** pour démontrer toutes les fonctionnalités de la plateforme.

---

## 1. Vue d'ensemble

| Domaine | Tables peuplées | Volume DEMO |
| --- | --- | --- |
| **RBAC** | users, roles, permissions | 30 comptes (`demo.userXX@ceeac.int`) |
| **Institution** | departements, directions, partenaires, sources_financement | 7 départements, 20 services/directions, 5 partenaires |
| **Chaîne RBM/GAR** | papas, axes, produits, sous_produits, activites, taches | 1 PAPA · 10 axes · 30 produits · 60 sous-produits · 180 activités · 540 tâches |
| **Indicateurs CMR** | indicateurs, valeurs_indicateurs | 120 indicateurs · 480 mesures trimestrielles |
| **Budget IPSAS** | budget_exercices, budget_lignes, budget_mouvements, budgets (opérationnels) | 5 exercices (Y-2 à Y+2) · 100 lignes · 180 mouvements (engagement/liquidation/paiement) · 64 budgets opérationnels |
| **Imports Excel** | budget_imports, budget_import_erreurs, budget_import_mappings | 5 imports (statuts variés) · 9 erreurs · 1 mapping |
| **Workflow** | validations, alertes | 10 validations · 10 alertes |
| **Audit interne IGS** | audit_plans, audit_missions, audit_constats, audit_recommandations, audit_suivis_recommandations | 1 plan · 4 missions · 4 constats · 4 recommandations · 4 suivis |
| **Reporting** | generated_reports | 5 rapports (PDF/XLSX) |
| **Notifications** | notifications (uuid) | 20 notifications utilisateurs |
| **Activity log** | activity_log (Spatie auto) | généré automatiquement par les modèles `LogsActivity` |

**Total : ~2 000 lignes de données institutionnelles cohérentes.**

---

## 2. Commande Artisan

```bash
php artisan tbpapa:seed-demo [options]
```

### Options

| Option | Description |
| --- | --- |
| `--append` *(défaut)* | Ajoute les données sans supprimer l'existant (idempotent grâce à `firstOrCreate`) |
| `--fresh` | Supprime UNIQUEMENT les données DEMO avant de réinsérer |
| `--rollback` | Supprime uniquement les données DEMO (institutionnelles intactes) |
| `--year=2026` | Année centrale (exercices générés de Y-2 à Y+2) |
| `--departments=all` | Périmètre départements (placeholder pour filtrage futur) |
| `--with-audit` | Active les missions/constats/recommandations IGS |
| `--with-imports` | Active les imports Excel simulés + erreurs |
| `--with-notifications` | Active les notifications utilisateurs |
| `--with-kpi-history` | Active l'historique KPI et rapports générés |

### Exemples

```bash
# Démo complète, année 2026
php artisan tbpapa:seed-demo --append --with-audit --with-imports --with-notifications --with-kpi-history

# Réinitialiser uniquement les données DEMO (institutionnel préservé)
php artisan tbpapa:seed-demo --fresh

# Supprimer toutes les données DEMO
php artisan tbpapa:seed-demo --rollback

# Démo sur 2027
php artisan tbpapa:seed-demo --year=2027 --with-audit
```

---

## 3. Garanties de sécurité

| Garantie | Implémentation |
| --- | --- |
| **Préservation institutionnelle** | Rollback filtré sur `code LIKE 'DEMO-%'`, `email LIKE 'demo.%@ceeac.int'`, `version = 'DEMO'`, `observations LIKE '%DEMO%'` |
| **Idempotence** | Tous les inserts via `firstOrCreate` → ré-exécution sans doublons |
| **Production** | Confirmation explicite requise si `APP_ENV=production` |
| **Journalisation** | `Log::info('tbpapa:seed-demo started/completed')` |
| **Transaction** | `DB::transaction()` enveloppe l'exécution complète |

---

## 4. Cohérence garantie (vérifiée par tests)

`tests/Feature/DemoSeederTest.php` — **10 tests, 71 assertions, 100 % passing** :

1. ✅ Volumes minimums atteints (30 users, 7 dép., 10 axes, ≥150 activités, ≥500 tâches, etc.)
2. ✅ Chaîne RBM/GAR cohérente : Axe → Produit → Sous-Produit → Activité → Tâche (aucun orphelin)
3. ✅ Règle budgétaire **Total = CEEAC-EM + PTF** respectée sur toutes les lignes
4. ✅ Indicateurs rattachés au niveau Sous-Produit (CMR)
5. ✅ Exercices budgétaires multi-années (Y-2 à Y+2)
6. ✅ Audit interne IGS : chaîne plan → mission → constat → recommandation → suivi
7. ✅ Imports Excel avec statuts variés et erreurs réalistes
8. ✅ Rapports générés avec code de vérification cryptographique
9. ✅ Rollback supprime uniquement les données DEMO (institutionnel intact)
10. ✅ Commande Artisan idempotente (ré-exécution sans doublons)

---

## 5. Comptes utilisateurs

### Comptes institutionnels (réels — `InstitutionSeeder`)

| Email | Rôle |
| --- | --- |
| `president@ceeac.org` | Président |
| `vp@ceeac.org` | Vice-Président |
| `sg@ceeac.org` | Secrétaire Général |
| `commissaire.{daec,apsar,dge,dpsih,dedd,diem}@ceeac.org` | Commissaires |
| `directeur.{xxx}@ceeac.org` | Directeurs techniques + d'appui |
| `audit.interne@ceeac.org` | Auditeur interne |
| `controle.financier@ceeac.org` | Contrôleur financier |
| `admin@ceeac.org` | Administrateur DSI |

**Mot de passe initial : `Password@2026`**

### Comptes DEMO (30 utilisateurs)

| Email | Pattern |
| --- | --- |
| `demo.user01@ceeac.int` à `demo.user30@ceeac.int` | Rôles cyclés sur 11 profils (admin_technique, president, sg, commissaire, directeur_technique, directeur_appui, controle_financier, audit_interne, point_focal, ordonnateur, comptable) |

**Mot de passe : `Password@2026`**

---

## 6. Statuts variés représentés

| Entité | Statuts présents |
| --- | --- |
| Axes | valide, en_validation, soumis |
| Produits | valide, soumis, en_validation |
| Sous-produits | valide, soumis, brouillon |
| Activités | planifiee, en_cours, realisee, suspendue |
| Tâches | planifiee, en_cours, realisee, suspendue |
| Lignes budget | brouillon, importe, controle, valide |
| Exercices | brouillon, valide, cloture |
| Missions audit | planifiee, en_cours, projet_rapport, cloturee |
| Recommandations | ouverte, en_cours, verifiee, mise_en_oeuvre |
| Alertes | ouverte, en_traitement, resolue |
| Imports | reussi, prevu, echec, a_corriger |

---

## 7. KPI réalistes inclus

Calculés/dérivés depuis les valeurs seedées :

- **Taux d'exécution physique** : par axe/produit/sous-produit (champ `taux_execution` 0-100 %)
- **Taux d'exécution budgétaire** : `(engagement / prevision)` sur les budgets opérationnels
- **Taux de consommation** : `(consommation / engagement)`
- **Taux de financement CEEAC / PTF** : `(montant_ceeac_em / total)` et `(montant_ptf / total)` par ligne
- **Taux de réalisation par département** : agrégat via `departement_id` sur axes/lignes
- **Taux de validation** : ratio `Validation::decision = approuve / total`
- **Écart budgétaire** : `variation` (en %) sur chaque ligne
- **Coût par activité** : via `Budget` polymorphe + `budgetable_type = Activite`
- **Taux de retard** : recommandations dont `date_echeance < now()` et statut non clôturé

Tous ces KPI sont consommés par les dashboards existants (`/dashboard`, `/budget`, `/audit`).

---

## 8. Données budgétaires — structure CEEAC

### Nomenclature respectée

```
Titre 1, 2, 3  →  Chapitre 60-72  →  Article 601-7411  →  Paragraphe 6011-72111  →  Ligne 60111-72111
```

Exemples réels seedés :
- **Recettes Titre 1** : Contributions des 11 États membres (`72101` Angola à `72111` Tchad)
- **Recettes Titre 2** : Dons PTF (BAD, UE, UA/AUDA-NEPAD, BM, ONUDI, ONU-AC, etc.)
- **Dépenses** : Personnel, biens et services, transferts, équipements, 6 piliers PAPA

### Champs ligne budgétaire

Chaque ligne intègre : `budget_ligne_code`, `libelle`, `montant_total`, `montant_ceeac_em`, `montant_ptf`, `exercice_id`, `departement_id`, `direction_id`, `activite_id`, `tache_id`, `source_financement_id`, `statut`, `dates`, `created_by`, `pilier`, `variation`, `taux_realisation_precedent`.

---

## 9. Cycle IPSAS — Mouvements budgétaires

Pour 60 lignes budget, génération automatique de la chaîne IPSAS complète :

```
Engagement → Liquidation → Paiement
   ↑              ↑            ↑
ordonnateur  comptable     virement/cheque
```

Chaque mouvement contient : `parent_mouvement_id` (chaînage), `numero_piece`, `beneficiaire_nom`, `ordonnateur_id`, `comptable_id` (séparation COSO), `mode_paiement`, `statut_mouvement`, `valide_par_id`.

---

## 10. Limites & évolutions

- Le seeder ne génère pas de fichiers Excel/PDF physiques (`storage/`) — seules les métadonnées sont en base.
- Pas d'OTP 2FA configuré sur les comptes demo (login direct possible si MFA désactivé pour ces comptes).
- Les permissions sont synchronisées via `syncRoles` — pour rajouter un permission/rôle après seed, ré-exécuter `db:seed --class=RolesPermissionsSeeder`.

---

**Livré le 2026-05-27** — Cohérence vérifiée par 10 tests Feature.
