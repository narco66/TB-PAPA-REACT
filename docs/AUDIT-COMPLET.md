# AUDIT INSTITUTIONNEL COMPLET — TB-PAPA-CEEAC

**Application** : Tableau de Bord du Plan d'Actions Prioritaires Annuel (PAPA) — Commission de la CEEAC
**Stack** : Laravel 13 · Inertia v3 · React 19 · TypeScript · Tailwind v4 · MySQL
**Date d'audit** : 27 mai 2026
**Auditeur** : Revue interne assistée — non destructive
**Référentiels mobilisés** : RBM/GAR (CEEAC), CDC fonctionnel & technique, COSO ERM 2017, IPSAS, ISO/IEC 27001:2022, ISO 9001:2015, COBIT 2019, ITIL 4, RGPD/UE 2016/679, OWASP ASVS 4.0, AfDB Results Reporting, World Bank PforR, UN Strategic Planning Framework.

---

## 0. SYNTHÈSE EXÉCUTIVE

### 0.1 Niveau global de maturité

| Dimension | Maturité (0–5) | Tendance | Observation principale |
|---|---|---|---|
| **Architecture technique** | 4.2 / 5 | ↗ | Stack moderne, séparation des responsabilités correcte, observers + services bien posés. |
| **Conformité RBM/GAR** | 4.0 / 5 | ↗ | Chaîne officielle Axe → Tâche implémentée intégralement, codification automatique opérationnelle. |
| **Couverture fonctionnelle** | 3.6 / 5 | → | Modules cœur livrés ; modules amont (planification stratégique, scénarios) et aval (clôture, capitalisation) à compléter. |
| **Budget & finances** | 3.5 / 5 | → | Distinction CEEAC-EM / PTF en place ; engagements / mandatements / soldes restent à modéliser. |
| **Reporting & GED** | 4.5 / 5 | ↗↗ | 17 rapports institutionnels, intégrité SHA-256 + QR, audit trail complet — point fort du système. |
| **Sécurité & conformité** | 3.4 / 5 | ↗ | RBAC Spatie + Activity Log présents ; manquent MFA, RGPD/registre, plan de chiffrement applicatif. |
| **UX / Ergonomie** | 3.8 / 5 | ↗ | shadcn cohérent, sidebar lifecycle ordonné ; manque tour onboarding et accessibilité WCAG. |
| **Documentation** | 3.7 / 5 | → | 3 docs d'audit, README implicite ; manque doc utilisateur final, runbook ops, ADR. |
| **Tests & qualité** | 2.2 / 5 | ↘ | 4 fichiers de tests pour ~70 features ; couverture < 10 %. **Point critique.** |
| **DevOps & exploitation** | 2.5 / 5 | → | Pas de CI/CD documenté, pas de monitoring, pas de plan de bascule production. |

**Score moyen** : **3.54 / 5** — *Application en stade pré-production avancé, prête pour pilote interne mais non encore éligible à mise en production institutionnelle sans renforcement sécurité + tests + exploitation.*

### 0.2 Top 10 des constats prioritaires

| # | Constat | Sévérité | Effort |
|---|---|---|---|
| 1 | Couverture tests < 10 % — risque régression élevé en exploitation | 🔴 Critique | M |
| 2 | Pas de MFA / 2FA sur les comptes admin & présidence | 🔴 Critique | S |
| 3 | Budget : pas de cycle engagement → ordonnancement → mandatement → paiement (IPSAS) | 🔴 Critique | L |
| 4 | Aucune CI/CD ni monitoring (Sentry / Telescope / Horizon) | 🔴 Critique | M |
| 5 | Pas de workflow de validation hiérarchique modélisé (BPMN) | 🟠 Élevée | M |
| 6 | RGPD : registre des traitements absent, DPO non identifié | 🟠 Élevée | S |
| 7 | Aucun mécanisme d'archivage légal au-delà des PDF (rétention, purge) | 🟠 Élevée | M |
| 8 | Accessibilité WCAG 2.1 AA non vérifiée | 🟡 Moyenne | M |
| 9 | Pas de scénarisation (PAPA simulé / révisé / arrêté) ni versioning institutionnel | 🟡 Moyenne | L |
| 10 | Indicateurs : pas de typologie OCDE (résultat / processus / impact) ni baseline / cible / palier | 🟡 Moyenne | S |

### 0.3 Verdict global

> **L'application TB-PAPA-CEEAC constitue un socle institutionnel sérieux, conforme à la chaîne RBM officielle de la Commission et adossé à un moteur documentaire de qualité institutionnelle (QR + hash + audit trail). Elle est exploitable en pilote restreint. Sa montée en production exige (i) une consolidation sécurité (MFA, RGPD, chiffrement), (ii) une élévation drastique de la couverture tests, (iii) la finalisation du cycle budgétaire IPSAS et (iv) l'industrialisation DevOps (CI/CD, monitoring, sauvegarde).**

---

## 1. AUDIT STRATÉGIQUE & INSTITUTIONNEL

### 1.1 Alignement avec la mission de la CEEAC

La CEEAC, en tant que Communauté Économique Régionale (CER) de l'Union Africaine, opère selon une logique :

- **Pilotage stratégique** : Vision 2050 → Plan stratégique → PAPA annuel
- **Mise en œuvre** : Départements (DAEC, DAPSAR, DGE, DPSIH, DEDD, DIEM) + Directions
- **Suivi-évaluation** : RBM/GAR avec indicateurs Cadre de Mesure du Rendement (CMR)
- **Reddition** : Rapport semestriel / annuel aux États membres et bailleurs

| Exigence institutionnelle | Couverture actuelle | Niveau | Recommandation |
|---|---|---|---|
| Vision 2050 → PAPA hiérarchisée | Modélisation PAPA présente (`papas` table) | 🟢 75 % | Ajouter table `plans_strategiques` parent de PAPA |
| Cycle annuel PAPA | Une instance PAPA = une année | 🟢 OK | Ajouter révisions intra-annuelles (PAPA T1/T2) |
| Multi-organes (Conférence, Conseil, Commission) | Absent | 🔴 0 % | Modéliser organes statutaires |
| Multi-États membres (11 États) | Absent | 🟠 0 % | Table `etats_membres` + ventilation territoriale |
| Multi-langues officielles (FR/EN/ES/PT) | FR uniquement | 🟠 25 % | i18n laravel + react-i18next |
| Cycle PPBES (Planification, Programmation, Budgétisation, Exécution, Suivi) | Partiel : P+B+E+S | 🟡 60 % | Module "Programmation" et "Évaluation" à renforcer |

### 1.2 Cartographie organes ↔ application

- **Présidence / Vice-Présidence** : rôles présents (`president`, `vice_president`) ✅
- **Commissariats sectoriels** : rôle `commissaire` ✅
- **Secrétariat Général** : rôle `secretaire_general` ✅
- **Directions techniques (9) & directions d'appui (5)** : seedées via `InstitutionSeeder` ✅
- **Audit interne (IGS)** : rôle `audit_interne` ✅
- **Contrôle financier (CF)** : rôle `controle_financier` ✅
- **Partenaires techniques et financiers (PTF)** : rôle `partenaire`, table `partenaires` seedée (6 entrées) ✅
- **Conférence des Chefs d'État** : ❌ absent
- **Conseil des Ministres** : ❌ absent
- **Comité des Ambassadeurs / Comité Tripartite** : ❌ absent

> 🔴 **Constat 1.A** — Les organes de décision politique (Conférence, Conseil) ne sont pas modélisés. Or, l'approbation finale du PAPA et la reddition de comptes leur sont réservées. Impact : impossible de produire les actes officiels (décisions, résolutions) directement depuis l'application.

### 1.3 Alignement avec les référentiels supranationaux

| Référentiel | Alignement | Niveau |
|---|---|---|
| Agenda 2063 (UA) — 7 aspirations, 20 objectifs | Non modélisé (pas de table `agenda_2063_objectifs`) | 🔴 |
| ODD 2030 (ONU) — 17 objectifs | Non modélisé | 🔴 |
| Programme Détaillé pour le Développement de l'Agriculture en Afrique (PDDAA) | Non modélisé | 🟠 |
| Cadre de Coopération CEEAC-UE / CEEAC-UA | Partiel : partenaires seedés | 🟡 |
| Stratégie de financement de l'UA | Non modélisé | 🟠 |

> 🟠 **Constat 1.B** — Aucun rattachement des activités PAPA aux ODD, à l'Agenda 2063 ou aux cadres sectoriels UA. C'est une exigence forte des bailleurs (BAD, BM, UE, FAO) et de la reddition de comptes pan-africaine. Recommandation : ajouter tables polymorphes `referentiels_externes` + `rattachements_referentiels`.

---

## 2. AUDIT FONCTIONNEL DÉTAILLÉ

### 2.1 Inventaire des modules livrés

| # | Module | Tables | Modèles | Contrôleur | Pages React | Politique | Tests | Statut |
|---|---|---|---|---|---|---|---|---|
| 1 | Authentification | users, sessions, password_resets | User | Auth/Authenticated... | login | (Gate) | ✅ | 🟢 OK |
| 2 | PAPA (stratégie) | papas | Papa | Papa/PapaController | papa/* | PapaPolicy | ❌ | 🟢 OK |
| 3 | Axes RBM | axes | Axe | Rbm/AxeController | rbm/axes/* | AxePolicy | ✅ | 🟢 OK |
| 4 | Produits RBM | produits | Produit | Rbm/ProduitController | rbm/produits/* | ProduitPolicy | ✅ | 🟢 OK |
| 5 | Sous-Produits RBM | sous_produits | SousProduit | Rbm/SousProduitController | rbm/sous-produits/* | SousProduitPolicy | ✅ | 🟢 OK |
| 6 | Activités | activites | Activite | Activite/ActiviteController | activites/* | ActivitePolicy | ❌ | 🟢 OK |
| 7 | Tâches | activite_taches | Tache | Rbm/TacheController | rbm/taches/* | TachePolicy | ❌ | 🟢 OK |
| 8 | Indicateurs | indicateurs, valeurs_indicateurs | Indicateur, ValeurIndicateur | IndicateurController | indicateurs/* | IndicateurPolicy | ❌ | 🟡 partiel |
| 9 | Budget (cadre) | budget_exercices, budget_lignes, ... | Budget/* | Budget/* (4) | budget/* | BudgetExercicePolicy, BudgetLignePolicy | ❌ | 🟡 partiel |
| 10 | Alertes | alertes | Alerte | AlerteController | alertes/index | – | ❌ | 🟢 OK |
| 11 | Documents (GED) | documents | Document | Document/DocumentController | documents/* | DocumentPolicy | ❌ | 🟡 partiel |
| 12 | Validations | validations | Validation | (intégré aux ressources) | – | – | ❌ | 🔴 incomplet |
| 13 | Reporting | generated_reports, scheduled_reports | GeneratedReport | Reporting/ReportController | reports/* | – | ❌ | 🟢 OK |
| 14 | Administration Users | – | User | Admin/UserController | admin/users/* | – | ❌ | 🟢 OK |
| 15 | Administration Départements | – | Departement | Admin/DepartementController | admin/departements/* | DepartementPolicy | ❌ | 🟢 OK |
| 16 | Audit | activity_log | (via Spatie) | Admin/AuditController | admin/audit/index | – | ❌ | 🟢 OK |
| 17 | Dashboard | – | – | DashboardController | dashboard/index | – | ❌ | 🟡 partiel |

### 2.2 Modules manquants ou partiels (gap analysis)

#### 2.2.1 Modules **manquants** (à créer)

| Module | Justification CDC / RBM | Priorité |
|---|---|---|
| **Plans stratégiques pluriannuels** | Le PAPA est une déclinaison annuelle ; il faut le rattacher à un plan 5–10 ans. | 🟠 Élevée |
| **Scénarisation budgétaire** | PAPA simulé / arrêté / révisé / clôturé (workflow). | 🟠 Élevée |
| **Programmation pluriannuelle** | CDMT (Cadre de Dépenses à Moyen Terme) sur 3 ans glissants. | 🟠 Élevée |
| **Cycle engagement / mandatement / paiement** | IPSAS exige les 4 étapes : engagement → liquidation → ordonnancement → paiement. | 🔴 Critique |
| **Risques institutionnels (registre)** | COSO ERM + ISO 31000 — registre des risques avec criticité, plan de réponse. | 🟠 Élevée |
| **Audit interne (missions, plans, recommandations)** | IGS doit pouvoir planifier, exécuter, suivre les missions et le suivi des recommandations. | 🟠 Élevée |
| **Évaluation (mi-parcours, finale, ex-post)** | Cycle RBM complet = Planification + Exécution + **Évaluation**. | 🟠 Élevée |
| **Capitalisation & leçons apprises** | Base de connaissances institutionnelle. | 🟡 Moyenne |
| **Gestion des partenariats (conventions, accords, financements)** | Table `partenaires` existe mais conventions absentes. | 🟡 Moyenne |
| **Marchés publics / passation** | Lien Plan de Passation des Marchés (PPM) ↔ Activités. | 🟡 Moyenne |
| **Trésorerie & engagements PTF** | Suivi cash flow institutionnel. | 🟡 Moyenne |
| **Réunions statutaires (CDM, Conférence)** | Convocations, ordres du jour, décisions. | 🟢 Faible |

#### 2.2.2 Modules **partiels** (à compléter)

##### Indicateurs (🟡)

État actuel :
- Table `indicateurs` avec valeurs liées.
- Pages create / index / show.
- Pas de typologie OCDE explicite (**impact / résultat / produit / processus**).
- Pas de **chaîne de causalité** indicateur → produit → axe.
- Pas de **baseline / cible annuelle / palier trimestriel**.
- Pas de **méthode de collecte / fréquence / source / responsable** structurés.
- Pas de **fiche signalétique d'indicateur** (PIM — Performance Indicator Metadata).

> 🔴 **Constat 2.A** — Le module indicateurs ne satisfait pas le format CMR (Cadre de Mesure du Rendement) attendu par BAD, BM, UE pour les financements basés sur les résultats. Ajouter : `type_indicateur` (impact/effet/produit/processus), `unite`, `baseline`, `cible_annuelle`, `paliers` (trimestriels), `methode_collecte`, `source_donnees`, `frequence`, `responsable_collecte`, `responsable_validation`, `desagregation_genre`, `desagregation_geographique`.

##### Budget (🟡)

État actuel :
- Exercices, lignes, sources financement, mouvements, imports.
- Distinction CEEAC-EM / PTF en place.
- Pas de cycle d'exécution comptable :
  - ❌ Engagement (réservation budgétaire)
  - ❌ Liquidation (constatation de service fait)
  - ❌ Ordonnancement (titre de paiement)
  - ❌ Paiement effectif
  - ❌ Soldes (disponible / engagé / mandaté / payé)

> 🔴 **Constat 2.B** — Le module budgétaire est conforme à la **présentation** (lignes, sources) mais pas à l'**exécution comptable IPSAS** attendue d'une commission régionale. Aucune piste d'engagement / mandatement / paiement.

##### Documents (🟡)

État actuel :
- Création + listing.
- Pas de versioning explicite (versions multiples du même document).
- Pas de cycle de vie (brouillon → revue → validé → publié → archivé).
- Pas de rétention légale / purge automatique.
- Pas de classification de confidentialité (Public / Interne / Restreint / Confidentiel).

##### Validations (🔴 incomplet)

État actuel :
- Table `validations` polymorphe créée.
- ❌ Pas de workflow modélisé (états, transitions, acteurs autorisés par étape).
- ❌ Pas d'interface React dédiée.
- ❌ Pas de notifications.
- ❌ Pas de visa électronique formel.

> 🔴 **Constat 2.C** — La validation hiérarchique est une exigence institutionnelle forte (signature présidentielle, visa commissaire, contre-seing CF). Sa modélisation actuelle est embryonnaire.

### 2.3 Workflows métier critiques

| Workflow | État | Constat |
|---|---|---|
| Création PAPA → Approbation Présidence | ❌ Non modélisé | Doit suivre : projet → revue Commissaires → arrêté SG → adopté Présidence. |
| Soumission Axe → Validation hiérarchique | ❌ Non modélisé | Idem ; aucun visa formel. |
| Mise à jour avancement Tâche → Recalcul cascade | ✅ Opérationnel (Observer) | Recalcul bottom-up par moyenne pondérée fonctionne. |
| Génération rapport → Diffusion | 🟡 Partiel | Génération + archivage OK ; pas de diffusion email/notification. |
| Constatation alerte → Plan d'action | 🟡 Partiel | Alertes listées ; aucun suivi de réponse. |
| Mouvement budgétaire → Mise à jour ligne | ✅ Opérationnel | Modèle `BudgetMouvement` présent. |
| Import budget Excel → Lignes | ✅ Opérationnel | Service `BudgetImportService` OK. |
| Production états périodiques (mensuels, trimestriels) | ❌ Manuel | `scheduled_reports` table présente mais pas de planificateur. |

---

## 3. AUDIT RBM / GAR

### 3.1 Conformité à la chaîne officielle CEEAC

Chaîne attendue : **Axe → Produit → Sous-Produit → Activité → Tâche** (5 niveaux).

| Niveau | Table | Codification | Observer | Poids | Recalcul cascade | Avancement | Statut |
|---|---|---|---|---|---|---|---|
| 1. Axe | `axes` | `AXE N` | ✅ | ✅ | ✅ remontée | ✅ | 🟢 Conforme |
| 2. Produit | `produits` | `P.N.M` | ✅ | ✅ | ✅ | ✅ | 🟢 Conforme |
| 3. Sous-Produit | `sous_produits` | `SP.N.M.K` | ✅ | ✅ | ✅ | ✅ | 🟢 Conforme |
| 4. Activité | `activites` | `ACT.N.M.K.L` | ✅ | ✅ | ✅ | ✅ | 🟢 Conforme |
| 5. Tâche | `activite_taches` | `T.N.M.K.L.P` | ✅ | ✅ | – (feuille) | ✅ | 🟢 Conforme |

> ✅ **Verdict 3.A** — La chaîne RBM officielle est **intégralement et correctement implémentée**. La codification automatique (`CodificationService`) et la propagation bottom-up via observer (`RbmCodificationObserver`) sont des points forts.

### 3.2 Conformité au cadre RBM (5 piliers)

| Pilier RBM | Couverture | Niveau |
|---|---|---|
| **Leadership** : engagement haut niveau, communication, vision | Partielle : Président/Vice-Président modélisés mais pas de dashboard exécutif dédié à la direction. | 🟡 |
| **Planification stratégique** : axes, produits, indicateurs SMART | OK chaîne + indicateurs ; manque caractère SMART explicite. | 🟡 |
| **Mise en œuvre opérationnelle** : activités, responsabilisation, jalons | OK avec `est_jalon` sur Activité et RACI implicite. | 🟢 |
| **Suivi et reporting** : tableaux de bord, indicateurs, revue | OK dashboard + 17 rapports ; manque revue de performance institutionnelle structurée. | 🟢 |
| **Évaluation et apprentissage** : leçons apprises, capitalisation | ❌ Absent. | 🔴 |

### 3.3 Conformité au Cadre de Mesure du Rendement (CMR)

Format attendu (BAD/BM/UE) :

```
Indicateur | Type | Unité | Baseline | Cible An1 | Cible An2 | ... | Méthode | Fréquence | Source | Responsable
```

État actuel : champs partiels uniquement (`code`, `libelle`, `valeur`, `unite`, `cible`).

> 🟠 **Constat 3.B** — Fiche signalétique indicateur incomplète (cf. 2.2.2). Bloquant pour reporting bailleurs.

### 3.4 Théorie du changement

Aucune représentation explicite de la **théorie du changement** (hypothèses, conditions, risques sur la chaîne logique).

> 🟡 **Constat 3.C** — Recommandation : ajouter champ `hypotheses` + `risques_associes` (JSON ou table dédiée) sur Axe, Produit, Sous-Produit.

### 3.5 Désagrégation (genre, géographie, vulnérabilité)

Exigence BAD/ONU : désagrégation systématique des bénéficiaires par sexe, âge, géographie, vulnérabilité.

> 🟠 **Constat 3.D** — Aucune table ni champ de désagrégation n'existe. À ajouter sur `ValeurIndicateur`.

---

## 4. AUDIT BUDGÉTAIRE & FINANCIER

### 4.1 Architecture budgétaire actuelle

```
BudgetExercice (annuel)
  ├── BudgetLigne (rattachée à Axe / Produit / Sous-Produit / Activité)
  │     ├── nature : recette | dépense
  │     ├── type_budget : recette_interne | recette_externe | fonctionnement | investissement | équipement | dotation | dette | transfert | autre
  │     ├── montant_ceeac_em
  │     ├── montant_ptf
  │     └── source_financement_id
  ├── BudgetSourceFinancement (États contributeurs, PTF)
  ├── BudgetMouvement (réallocations, virements)
  └── BudgetImport (traces des imports Excel)
```

### 4.2 Conformité IPSAS / nomenclature budgétaire

| Exigence IPSAS / nomenclature CEEAC | État | Niveau |
|---|---|---|
| Plan comptable analytique cohérent | ✅ via codification + type_budget | 🟢 |
| Classification économique (titres) | 🟡 partiel (`type_budget` proche) | 🟡 |
| Classification fonctionnelle (par axe) | ✅ | 🟢 |
| Classification administrative (par département/direction) | 🟡 indirect via activité | 🟡 |
| Classification par source de financement | ✅ | 🟢 |
| Sincérité budgétaire (équilibre recettes/dépenses) | ❌ pas de contrôle | 🔴 |
| Annualité (clôture, report) | ❌ pas modélisé | 🔴 |
| Spécialité (interdiction de mouvement sauf virement formalisé) | 🟡 mouvements présents mais pas de contrôle | 🟡 |
| Universalité (toutes recettes / dépenses) | ✅ couvert | 🟢 |
| Engagement → Liquidation → Ordonnancement → Paiement | ❌ absent | 🔴 |
| Disponible budgétaire (alerte avant dépassement) | ❌ | 🔴 |
| Engagements pluriannuels (AE/CP) | ❌ | 🔴 |

> 🔴 **Constat 4.A** — Conformité IPSAS partielle. Le budget tel que modélisé est **prévisionnel et descriptif**, pas **exécutif et comptable**. Pour passer en production, ajouter le cycle complet.

### 4.3 Alignement budget 2026 réel

Source : `BudgetSeeder` reproduit le budget réel 40 305 795 803 FCFA.

✅ Conformité montant total.
✅ Ventilation CEEAC-EM / PTF.
✅ Axes principaux représentés.

⚠️ Absence d'**audit de cohérence** automatique : il faudrait pouvoir comparer mensuellement réalisations vs prévisions, et alerter sur écarts > seuil.

### 4.4 Contrôles internes financiers

| Contrôle COSO | État | Niveau |
|---|---|---|
| Séparation des tâches (ordonnateur ≠ payeur) | ❌ pas implémenté | 🔴 |
| Approbation hiérarchique mouvement | 🟡 traçabilité présente | 🟡 |
| Double-signature au-delà d'un seuil | ❌ | 🔴 |
| Rapprochement bancaire | ❌ hors périmètre app | — |
| Audit trail des modifications | ✅ via Spatie ActivityLog | 🟢 |
| Réconciliation trimestrielle | ❌ | 🔴 |

---

## 5. AUDIT TECHNIQUE (LARAVEL / INERTIA / REACT)

### 5.1 Architecture backend Laravel

#### 5.1.1 Conformité aux principes Laravel

| Principe | Application | Niveau |
|---|---|---|
| Conventions de nommage (PSR-1/12) | ✅ respectées | 🟢 |
| Single Responsibility Controllers | ✅ contrôleurs orientés ressource | 🟢 |
| Form Requests pour validation | 🟡 partiellement utilisés (à vérifier sur tous les Create/Update) | 🟡 |
| Eloquent Relations propres | ✅ | 🟢 |
| Resource / API Resource pour transformer données | ❌ pas utilisé (les contrôleurs passent les modèles bruts via Inertia) | 🟡 |
| Services pour logique métier | ✅ `Services/Rbm`, `Services/Reporting`, `Services/Budget` | 🟢 |
| Observers pour effets de bord | ✅ `RbmCodificationObserver` | 🟢 |
| Policies pour autorisation | ✅ 12 policies | 🟢 |
| Soft deletes | ✅ généralisé | 🟢 |
| Queues (jobs asynchrones) | ❌ tout est synchrone (PDF, recalculs) | 🟠 |
| Caching applicatif | ❌ pas vu | 🟠 |
| Events / Listeners | 🟡 implicite via Observers | 🟡 |

> 🟠 **Constat 5.A** — Génération PDF synchrone (DomPDF). Risque sur gros rapports (Journal d'audit, Historique > 2000 lignes) : timeout HTTP. Recommandation : passer en jobs files d'attente + notification utilisateur quand prêt + téléchargement.

#### 5.1.2 Migrations & schéma BDD

- 27 migrations propres, datées 2026.
- Soft deletes systématiques.
- Foreign keys avec `onDelete` cohérents (à vérifier individuellement).
- Index composites présents (à auditer plus finement).

**Points à vérifier :**
- Index sur colonnes filtrées fréquemment (ex. `papas.annee`, `activites.statut`, `budget_lignes.exercice_id`).
- Contraintes `unique` sur codes (à confirmer pour Axe, Produit, etc.).
- Cohérence des types décimaux (montants budgétaires : DECIMAL(18,2) ?).

#### 5.1.3 Couche service

| Service | Responsabilité | Qualité |
|---|---|---|
| `Rbm/CodificationService` | Génération codes hiérarchiques | 🟢 propre |
| `Rbm/RecalculAvancementService` | Bottom-up moyenne pondérée | 🟢 propre |
| `Budget/BudgetImportService` | Import Excel | 🟢 |
| `Budget/BudgetExportService` | Export Excel | 🟢 |
| `Reporting/ReportRegistry` | Registry pattern | 🟢 excellent |
| `Reporting/PdfGeneratorService` | Génération PDF + QR + hash | 🟢 excellent |
| `AlerteService` | Détection retards | 🟡 fonctionnel mais à enrichir |

#### 5.1.4 Couche policies

12 policies couvrent les ressources principales. ✅ alignées sur le RBAC Spatie.
⚠️ À vérifier : présence systématique de `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

### 5.2 Architecture frontend (Inertia + React 19)

| Aspect | État | Niveau |
|---|---|---|
| TypeScript strict | À vérifier (`tsconfig.json`) | 🟡 |
| Types des props Inertia | ❌ majoritairement `any` (vu sur historique.tsx, lignes/index.tsx, etc.) | 🔴 |
| Composants shadcn cohérents | ✅ | 🟢 |
| Composants réutilisables (`PdfQuickButton`, `RbmStatusBadge`, `StatusBadge`) | ✅ | 🟢 |
| State management | Inertia + useState/useForm local — suffisant | 🟢 |
| Code splitting / lazy load | ❌ tout est dans le bundle principal | 🟡 |
| Accessibilité (ARIA, focus management) | À auditer | 🟡 |
| Internationalisation | ❌ FR codé en dur | 🔴 |
| Gestion erreurs (boundary, toasts) | À vérifier | 🟡 |

> 🔴 **Constat 5.B** — L'usage massif de `any` (`{ rapports, filtres, categories }: any`) annule les bénéfices TypeScript. Recommandation : générer des types depuis les Resources Laravel ou définir des interfaces partagées (`resources/js/types/`).

### 5.3 Performance

| Aspect | Risque | Mesure |
|---|---|---|
| N+1 queries | Élevé (sur listes Axes/Produits/Activités avec relations) | À auditer avec Telescope/Debugbar |
| Eager loading systématique | À vérifier | – |
| Pagination cursor vs offset | Offset utilisé partout — OK pour < 100k lignes | – |
| Index manquants | À auditer | – |
| Cache Redis | Non configuré | – |
| Assets minifiés | ✅ via Vite | 🟢 |
| Bundle size React | À mesurer | – |

### 5.4 Code quality

- ❌ Pas de PHPStan / Larastan configuré.
- ❌ Pas de Pint / PHP-CS-Fixer documenté.
- ❌ Pas d'ESLint / Prettier vu (à vérifier package.json devDependencies).
- ❌ Pas de pre-commit hooks (husky, lint-staged).

> 🟠 **Constat 5.C** — Aucun outil de qualité de code automatisé n'est en place. Critique pour un projet institutionnel multi-développeurs.

---

## 6. AUDIT UX / UI — BENCHMARK INSTITUTIONNEL

### 6.1 Benchmark vis-à-vis des standards

| Institution | Pratique | TB-PAPA-CEEAC |
|---|---|---|
| **World Bank PforR Dashboards** | Indicateurs de résultats visibles dès l'accueil, codification couleur (vert/jaune/rouge), légende explicite | 🟡 dashboard présent mais minimaliste |
| **AfDB Results Reporting** | Fiches de projet PDF normalisées, QR de vérification, signature présidentielle | ✅ atteint (PDF institutionnel + QR + hash) |
| **Union Africaine STC** | Multilingue 4 langues, organes décisionnels modélisés | ❌ FR seul, organes absents |
| **UN Strategic Planning Framework** | Tour onboarding, glossaire RBM intégré, aide contextuelle | ❌ aucun |
| **UE Cohesion Open Data** | Open data API, ventilation territoriale | 🟡 ventilation par axe seulement |
| **OCDE Better Life** | Visualisation interactive (cartes, radar) | ❌ pas de cartes |

### 6.2 Sidebar & navigation

✅ Réorganisation en lifecycle (Pilotage → Planif → Exécution → Budget → Documents → Admin) — pertinent.
✅ Icônes lucide-react cohérentes.
🟡 Pas de recherche globale (cmd+k).
🟡 Pas de favoris / pinning.
🟡 Breadcrumbs présents mais non cliquables systématiquement.

### 6.3 Pages & écrans

#### Forces
- ✅ Listes avec filtres + recherche cohérentes.
- ✅ Cartes shadcn uniformes.
- ✅ Badges de statut codifiés.
- ✅ Boutons PdfQuickButton omniprésents — geste documentaire fluide.

#### Faiblesses
- 🟡 Aucun **onboarding** / tour guidé pour nouveaux utilisateurs.
- 🟡 Pas d'aide contextuelle (info-bulles, popovers explicatifs RBM).
- 🟡 Pas de **vue Gantt globale** (existe `activites/gantt.tsx` mais à vérifier intégration).
- 🟡 Dashboard exécutif léger : indicateurs de tête (KPIs) à enrichir (radar des axes, top 5 risques, top 10 retards).
- 🟠 Aucun **mode impression** dédié pour les écrans (mais PDF compense).
- 🟠 Pas de **mode sombre** (à vérifier — composants shadcn le supportent en théorie).

### 6.4 Accessibilité (WCAG 2.1 AA)

| Critère | État estimé |
|---|---|
| Contraste textes ≥ 4.5:1 | À vérifier |
| Navigation clavier complète | À vérifier |
| Focus visible | shadcn fournit par défaut — 🟢 probable |
| ARIA labels sur icônes | 🟡 partiel |
| Lecteurs d'écran (rôles, landmarks) | À auditer |
| Alternative texte images | N/A peu d'images |
| Taille texte ajustable | ✅ rem-based |
| Pas de flash > 3 Hz | ✅ |

> 🟠 **Constat 6.A** — Aucune attestation WCAG. Pour une institution publique, c'est une exigence (cf. directive UE 2016/2102, code RGAA pour francophones).

### 6.5 Mobile / Responsive

- ✅ Tailwind utilise `md:`, `sm:` — responsive de base.
- 🟡 Tables larges (8+ colonnes) non testées en viewport mobile.
- 🟠 Pas de version PWA ni mode déconnecté.

### 6.6 Tonalité institutionnelle

- ✅ Français institutionnel correct (vocabulaire RBM cohérent).
- ✅ Pas d'emojis hors contexte.
- ✅ Vocabulaire CDC respecté (Axe, Sous-Produit, Activité, Tâche).
- 🟡 Aucune charte graphique CEEAC explicite (logo, palette officielle, signature visuelle).

---

## 7. AUDIT DOCUMENTAIRE & REPORTING

### 7.1 Moteur de reporting

#### Architecture
- ✅ Pattern Registry : ajout d'un rapport = 1 classe PHP + 1 template Blade + 1 entrée registry.
- ✅ Classification en 6 catégories (Stratégique, Performance, RBM, Budget, Gouvernance, Audit, Analytique).
- ✅ 17 rapports livrés.
- ✅ Layout institutionnel commun (`institutional.blade.php`) : header, watermark, QR, hash, pagination.
- ✅ Génération paramétrable avec filtres URL → params validés.

#### Catalogue rapports (17)

| Catégorie | Rapport | Format | Statut |
|---|---|---|---|
| Stratégique | PAPA institutionnel complet | Portrait A4 | 🟢 |
| Stratégique | Synthèse exécutive | Portrait A4 | 🟢 |
| Performance | Fiche Axe | Portrait A4 | 🟢 |
| Performance | Fiche Produit | Portrait A4 | 🟢 |
| Performance | Fiche Sous-Produit | Portrait A4 | 🟢 |
| Performance | Fiche Activité | Portrait A4 | 🟢 |
| Performance | Fiche Tâche | Portrait A4 | 🟢 |
| RBM | Matrice RBM consolidée | Paysage A3 | 🟢 |
| RBM | Matrice des indicateurs (CMR) | Paysage A3 | 🟢 |
| Budget | Budget consolidé | Portrait A4 | 🟢 |
| Budget | Détail lignes budgétaires | Paysage A3 | 🟢 |
| Gouvernance | Matrice RACI | Paysage A3 | 🟢 |
| Gouvernance | Fiche département | Portrait A4 | 🟢 |
| Audit | Journal d'audit | Paysage A3 | 🟢 |
| Audit | Historique des rapports | Paysage A3 | 🟢 |
| Analytique | Tableau de bord exécutif | Paysage A3 | 🟢 |

> ✅ **Verdict 7.A** — Couverture documentaire institutionnelle solide. Point fort différenciant.

### 7.2 Intégrité & traçabilité

| Mécanisme | État |
|---|---|
| Code de vérification unique (alphanumérique) | ✅ |
| Hash SHA-256 stocké en BDD | ✅ |
| QR code de vérification | ✅ |
| Page publique `/rapports/verifier` | ✅ |
| Audit trail (qui, quand, paramètres) | ✅ |
| Watermark "RESTREINT" / classification | ✅ |
| Horodatage / timestamp | ✅ |
| Signature électronique cryptographique (PKI) | ❌ |
| Horodatage qualifié (eIDAS / TSA) | ❌ |

> 🟠 **Constat 7.B** — Intégrité documentaire de bon niveau mais sans signature qualifiée. Pour les actes engageant la Présidence, à terme prévoir intégration PKI / horodatage qualifié.

### 7.3 Rapports manquants identifiés

| Rapport | Justification |
|---|---|
| **Rapport semestriel d'exécution** | Obligation reddition mi-année. |
| **Rapport annuel d'exécution** | Idem annuelle. |
| **Rapport au Conseil des Ministres** | Format statutaire spécifique. |
| **Rapport à la Conférence des Chefs d'État** | Idem présidentiel. |
| **Note de conjoncture trimestrielle** | Pilotage rapproché. |
| **Aide-mémoire mission terrain** | Visites de suivi. |
| **Procès-verbal de réunion** | Comités, COPIL. |
| **Tableau de bord départemental hebdomadaire** | Pilotage opérationnel. |
| **Rapport contrôle financier** | Trimestriel CF. |
| **Plan d'audit annuel IGS + Rapports de mission** | Cycle audit interne. |
| **Rapport ODD / Agenda 2063** | Reporting supranational. |
| **Rapport bailleur (template BAD/BM/UE)** | Convention de financement. |

### 7.4 GED (Gestion électronique des documents)

État actuel : table `documents` minimaliste.
Lacunes :
- ❌ Pas de versioning.
- ❌ Pas de coffre-fort numérique (NF Z42-013 / NF 461).
- ❌ Pas de plan de classement institutionnel.
- ❌ Pas de mots-clés / taxonomies.
- ❌ Pas d'OCR pour PDF scannés.
- ❌ Pas de signature électronique.

---

## 8. AUDIT SÉCURITÉ & CONFORMITÉ

### 8.1 Authentification

| Aspect | État | OWASP / ASVS 4.0 |
|---|---|---|
| Hash mots de passe (bcrypt / argon2) | ✅ via Laravel | OK |
| Politique complexité | À vérifier (Laravel défaut : 8 chars) | 🟠 insuffisant pour Présidence |
| MFA / 2FA | ❌ | 🔴 critique pour rôles sensibles |
| Verrouillage après N échecs | À vérifier | 🟠 |
| Rate limiting login | Laravel défaut probablement | 🟡 |
| Expiration session | À vérifier `config/session.php` | 🟡 |
| Password reset sécurisé | ✅ Laravel | OK |
| SSO / SAML / OAuth | ❌ | 🟡 souhaitable institutionnel |

> 🔴 **Constat 8.A** — MFA obligatoire pour `president`, `vice_president`, `commissaire`, `secretaire_general`, `audit_interne`, `controle_financier`, `admin_*`. À ajouter via `laravel/fortify` ou `pragmarx/google2fa`.

### 8.2 Autorisation (RBAC)

✅ Spatie Permission v7.4 configuré.
✅ 13 rôles institutionnels modélisés.
✅ ~85 permissions atomiques.
✅ 12 Policies appliquant l'autorisation.

⚠️ À vérifier :
- Toutes les routes protégées par middleware `auth` ?
- Tous les contrôleurs utilisent `authorize()` ou middlewares de policy ?
- Les `@can` Inertia sont systématiquement propagés au React (props `can`) ?

### 8.3 Activity Log & traçabilité

✅ Spatie ActivityLog v4.12 actif.
✅ Page `/admin/audit` consultable.
✅ Polymorphique : tracé sur tous les modèles avec trait `LogsActivity`.

⚠️ Vérifier :
- Rétention configurée (ex. 7 ans IPSAS) ?
- Exportabilité pour audit externe ?
- Intégrité du log (immutabilité, append-only) ?
- Pas de PII surexposée dans `properties` (RGPD) ?

### 8.4 OWASP Top 10 (2021)

| Risque | État | Niveau |
|---|---|---|
| A01 Broken Access Control | RBAC + Policies | 🟢 |
| A02 Cryptographic Failures | bcrypt OK ; chiffrement BDD au repos ? | 🟡 |
| A03 Injection | Eloquent (ORM) → SQL injection couverte | 🟢 |
| A04 Insecure Design | Workflows validation absents — surface attaque sur états | 🟡 |
| A05 Security Misconfiguration | `APP_DEBUG` en prod ? Headers sec ? | À vérifier 🟠 |
| A06 Vulnerable Components | Composer / npm à scanner | 🟡 |
| A07 Identification & Authentication Failures | Pas de MFA | 🔴 |
| A08 Software & Data Integrity Failures | Hash PDF OK ; signed URLs ? | 🟡 |
| A09 Security Logging & Monitoring | ActivityLog OK ; SIEM ? alertes ? | 🟡 |
| A10 SSRF | Pas d'appels externes pilotés par user | 🟢 |

### 8.5 RGPD / Protection des données

| Exigence | État |
|---|---|
| Registre des traitements (art. 30) | ❌ |
| DPO désigné | ❌ |
| Mention information utilisateurs | ❌ |
| Consentement (cookies, traceurs) | À vérifier |
| Droit d'accès / rectification / effacement | ❌ pas de mécanisme |
| Portabilité | ❌ |
| AIPD (analyse impact) | ❌ |
| Privacy by design / by default | 🟡 |
| Sous-traitants (hébergeur, mail) | ❌ documentation |
| Notification violation < 72 h | ❌ procédure |
| Chiffrement des données sensibles | ❌ |
| Anonymisation logs > N jours | ❌ |

> 🔴 **Constat 8.B** — Conformité RGPD très partielle. Risque institutionnel et réputationnel élevé. CEEAC traite données personnelles (employés, partenaires, citoyens via bénéficiaires d'activités).

### 8.6 ISO/IEC 27001:2022

| Domaine ISO | Couverture |
|---|---|
| A.5 Politiques | ❌ pas de PSSI documentée |
| A.6 Organisation | ❌ pas de RSSI nommé |
| A.7 Ressources humaines | ❌ procédures onboarding/offboarding |
| A.8 Gestion des actifs | 🟡 modèles présents ; pas inventaire actifs |
| A.9 Contrôle d'accès | 🟢 RBAC |
| A.10 Cryptographie | 🟡 hash OK ; chiffrement repos ? |
| A.11 Sécurité physique | ❌ hors périmètre app mais à doc |
| A.12 Exploitation | ❌ procédures ops |
| A.13 Communications | ❌ HTTPS forcé à vérifier |
| A.14 Acquisition/dev | 🟡 |
| A.15 Fournisseurs | ❌ |
| A.16 Incidents | ❌ |
| A.17 Continuité | ❌ pas de PCA/PRA |
| A.18 Conformité | 🟡 partiel |

### 8.7 COBIT 2019 / ITIL 4

Aspects de gouvernance IT (gestion changement, capacité, disponibilité, incidents) : **non couverts** à ce stade — c'est attendu pour une application en phase build, à formaliser avant mise en production.

### 8.8 COSO ERM (gestion des risques)

Pas de registre de risques institutionnels modélisé (cf. §2.2.1). Bloquant pour conformité COSO.

---

## 9. AUDIT TESTS & QUALITÉ

| Indicateur | Valeur | Cible | Écart |
|---|---|---|---|
| Fichiers de tests Feature | 3 | ~50 | -94 % |
| Fichiers de tests Unit | 1 | ~30 | -97 % |
| Couverture de code (estimée) | < 10 % | ≥ 70 % | -86 % |
| Tests E2E (Playwright/Dusk) | 0 | quelques happy paths | 100 % |
| Tests de performance (k6/Locust) | 0 | seuils SLA | – |
| Tests d'intégration BDD | 1 (RbmCodificationTest) | tous services | -95 % |
| Tests Policies | 0 | tous policies | 100 % |

> 🔴 **Constat 9.A — RISQUE CRITIQUE** — La couverture de tests est nettement insuffisante pour une application institutionnelle. Toute évolution future expose à des régressions silencieuses. À élever **avant mise en production**.

---

## 10. AUDIT DEVOPS & EXPLOITATION

| Capacité | État | Niveau |
|---|---|---|
| Repository Git | À confirmer (`git init` ?) | 🟡 |
| Branching strategy (gitflow / trunk-based) | À documenter | 🟡 |
| CI (GitHub Actions / GitLab CI) | ❌ | 🔴 |
| CD (déploiement automatisé) | ❌ | 🔴 |
| Environnements (dev / staging / prod) | À confirmer | 🟡 |
| Variables d'environnement (.env) gérées | ✅ Laravel | 🟢 |
| Secrets management (Vault / SOPS) | ❌ | 🟠 |
| Monitoring applicatif (Sentry, Bugsnag) | ❌ | 🟠 |
| Monitoring infra (Prometheus, Grafana) | ❌ | 🟠 |
| Logs centralisés (ELK, Loki) | ❌ | 🟠 |
| Sauvegarde BDD automatisée | ❌ | 🔴 |
| Plan de bascule / DR | ❌ | 🔴 |
| Mises à jour sécurité OS / PHP / Composer / npm | ❌ procédure | 🟠 |
| Documentation runbook ops | ❌ | 🟠 |

> 🔴 **Constat 10.A** — Aucune chaîne DevOps industrialisée. Bloquant pour mise en production sereine.

---

## 11. MATRICE D'ANOMALIES (consolidée)

| ID | Constat | Domaine | Sévérité | Effort | Priorité |
|---|---|---|---|---|---|
| C-001 | Pas de MFA pour rôles sensibles | Sécurité | 🔴 | S | P0 |
| C-002 | Couverture tests < 10 % | Qualité | 🔴 | M | P0 |
| C-003 | Cycle engagement→paiement IPSAS absent | Budget | 🔴 | L | P0 |
| C-004 | Aucune CI/CD ni monitoring | DevOps | 🔴 | M | P0 |
| C-005 | RGPD : registre + DPO + droits | Conformité | 🔴 | S | P0 |
| C-006 | Sauvegarde BDD automatisée absente | DevOps | 🔴 | S | P0 |
| C-007 | Workflows validation hiérarchique non modélisés | Fonctionnel | 🟠 | M | P1 |
| C-008 | Organes statutaires (Conférence, Conseil) absents | Stratégique | 🟠 | M | P1 |
| C-009 | Indicateurs : typologie + baseline/cible/palier | RBM | 🟠 | S | P1 |
| C-010 | Rattachement ODD / Agenda 2063 absent | Stratégique | 🟠 | M | P1 |
| C-011 | Désagrégation genre/géo absente | RBM | 🟠 | S | P1 |
| C-012 | Génération PDF synchrone (timeout risque) | Technique | 🟠 | S | P1 |
| C-013 | Types React `any` partout | Technique | 🟠 | M | P1 |
| C-014 | Risques institutionnels (COSO ERM) absents | Conformité | 🟠 | M | P1 |
| C-015 | Évaluation (mi-parcours, finale, ex-post) absente | RBM | 🟠 | M | P1 |
| C-016 | GED : versioning, classement, OCR absents | Documentaire | 🟠 | L | P1 |
| C-017 | Multi-langues officielles CEEAC (FR/EN/ES/PT) | UX | 🟠 | L | P1 |
| C-018 | ISO 27001 : pas de PSSI, RSSI | Sécurité | 🟠 | M | P1 |
| C-019 | Tests Policies + Form Requests | Qualité | 🟠 | M | P1 |
| C-020 | Audit interne (missions, recommandations) | Fonctionnel | 🟠 | M | P1 |
| C-021 | WCAG 2.1 AA non attesté | UX/Conformité | 🟡 | M | P2 |
| C-022 | Dashboard exécutif minimaliste | UX | 🟡 | S | P2 |
| C-023 | Code splitting / lazy load React | Performance | 🟡 | S | P2 |
| C-024 | PHPStan / Pint / ESLint non configurés | Qualité | 🟡 | S | P2 |
| C-025 | Pas de recherche globale (cmd+k) | UX | 🟡 | S | P2 |
| C-026 | Onboarding / tour guidé absent | UX | 🟡 | S | P2 |
| C-027 | Aide contextuelle RBM (glossaire) | UX | 🟡 | S | P2 |
| C-028 | Rapports : 12 templates manquants identifiés | Reporting | 🟡 | L | P2 |
| C-029 | Sincérité / annualité budgétaires (contrôles) | Budget | 🟡 | M | P2 |
| C-030 | Théorie du changement non explicite | RBM | 🟡 | S | P3 |
| C-031 | Signature électronique qualifiée (eIDAS) | Conformité | 🟡 | L | P3 |
| C-032 | PWA / mode déconnecté | UX | 🟢 | L | P3 |
| C-033 | Cartographie territoriale (cartes) | UX | 🟢 | M | P3 |
| C-034 | API publique open data | Analytique | 🟢 | L | P3 |

**Légende sévérité** : 🔴 Critique · 🟠 Élevée · 🟡 Moyenne · 🟢 Faible
**Légende effort** : S (≤ 5 j/h) · M (5–20 j/h) · L (> 20 j/h)
**Légende priorité** : P0 (bloquant production) · P1 (avant pilote large) · P2 (V1 stable) · P3 (V2+)

---

## 12. RECOMMANDATIONS HIÉRARCHISÉES

### 12.1 Quick Wins (≤ 1 semaine)

| # | Action | Bénéfice immédiat |
|---|---|---|
| QW-1 | Activer MFA Fortify (Laravel) pour rôles président/commissaire/admin | Élimine constat C-001 |
| QW-2 | Configurer PHPStan niveau 5 + Laravel Pint + ESLint + Prettier | Qualité de code automatisée |
| QW-3 | Ajouter middleware `force.https` + headers sécurité (HSTS, CSP, X-Frame-Options) | Sécurité réseau |
| QW-4 | Sauvegarde BDD quotidienne (mysqldump + rotation 30 j) via cron | Anti-perte critique |
| QW-5 | Ajouter Telescope (dev) + Sentry (prod) | Observabilité immédiate |
| QW-6 | Compléter fiche indicateur : baseline / cible / palier / méthode | C-009 |
| QW-7 | Form Requests systématiques avec règles strictes | Sécurité validation |
| QW-8 | Documenter PSSI (1 page) + nommer RSSI / DPO | Conformité formelle |
| QW-9 | Ajouter rate limiting login + verrouillage 5 échecs | C-001 |
| QW-10 | Générer types TypeScript depuis modèles (laravel-typescript-transformer) | C-013 |

### 12.2 Chantiers P0 (avant production)

1. **Sécurité & RGPD** (2–3 semaines)
   - MFA généralisé.
   - Registre RGPD + procédures droits.
   - Chiffrement données sensibles (Laravel `encrypted` casts pour matricules, identifiants partenaires).
   - HTTPS forcé + headers sec + cookie `Secure` + `HttpOnly` + `SameSite`.

2. **Tests** (3–4 semaines)
   - Tests Feature : 1 par contrôleur (CRUD + autorisation).
   - Tests Unit : 1 par service.
   - Tests Policy : tous policies.
   - Tests intégration : Observer codification + recalcul.
   - Couverture cible : 60 %+.

3. **Budget IPSAS** (4 semaines)
   - Tables `budget_engagements`, `budget_liquidations`, `budget_mandats`, `budget_paiements`.
   - Workflow : Engagement → Liquidation → Ordonnancement → Paiement.
   - Soldes en temps réel : disponible, engagé, mandaté, payé.
   - Contrôle disponibilité avant engagement.

4. **DevOps & exploitation** (2 semaines)
   - Repo Git + GitHub Actions (build, test, lint).
   - Environnements dev/staging/prod.
   - Sentry + monitoring infra (UptimeRobot / Better Stack).
   - Sauvegardes + restauration testée.
   - Runbook exploitation.

### 12.3 Chantiers P1 (avant pilote large)

5. **Workflows validation hiérarchique** (3 semaines)
   - State machine (`spatie/laravel-model-states`).
   - États par ressource : brouillon → soumis → revue1 → revue2 → approuvé.
   - Acteurs autorisés par état (matrice).
   - Notifications.

6. **Organes statutaires** (2 semaines)
   - Tables `organes_statutaires`, `sessions_organes`, `decisions`, `resolutions`.
   - Lien décision → PAPA / Axe / Activité.

7. **Évaluation RBM** (3 semaines)
   - Évaluations mi-parcours, finale, ex-post.
   - Critères CAD/OCDE (pertinence, efficacité, efficience, durabilité, impact).
   - Capitalisation : leçons apprises.

8. **Risques institutionnels** (2 semaines)
   - Registre des risques (probabilité × impact).
   - Plan de réponse (éviter / réduire / transférer / accepter).
   - Suivi périodique.

9. **Audit interne IGS** (3 semaines)
   - Plan d'audit annuel.
   - Missions (lettre de mission, équipe, périmètre, calendrier).
   - Constats + recommandations + suivi de mise en œuvre.

10. **Multi-langues** (4 semaines)
    - Laravel `lang/` pour back.
    - `react-i18next` pour front.
    - Couverture FR (existant) + EN + ES + PT.

### 12.4 Chantiers P2 (V1 stable, 3–6 mois)

- Accessibilité WCAG 2.1 AA (audit + corrections).
- Dashboard exécutif enrichi (cartes, radars, top N).
- Documents : versioning + classification + coffre-fort numérique.
- Rapports manquants (12 identifiés).
- Code splitting + bundle optimisation.
- Recherche globale (cmd+k).

### 12.5 Chantiers P3 (V2+, 6–12 mois)

- Signature électronique qualifiée (eIDAS).
- API publique open data.
- PWA / mode déconnecté.
- Cartographie territoriale interactive.
- Intégration SSO institutionnel (SAML/OIDC).

---

## 13. ARCHITECTURE CIBLE

### 13.1 Vue logique cible

```
┌─────────────────────────────────────────────────────────────────┐
│  PLAN STRATÉGIQUE PLURIANNUEL (Vision 2050 → 2030 → 5 ans)      │
│                          ↓                                       │
│  PAPA Annuel (cycle PPBES : Planif, Programm, Budgétisation,    │
│                            Exécution, Suivi)                     │
│                          ↓                                       │
│  CHAÎNE RBM : Axe → Produit → Sous-Produit → Activité → Tâche   │
│                          ↓ ↑                                     │
│  INDICATEURS (CMR) : impact / effet / produit / processus       │
│                          ↓                                       │
│  BUDGET (IPSAS) : Engag. → Liquid. → Ordonn. → Paiement         │
│                          ↓                                       │
│  EXÉCUTION : workflows validation, jalons, alertes              │
│                          ↓                                       │
│  ÉVALUATION : mi-parcours, finale, ex-post, capitalisation      │
│                          ↓                                       │
│  REDDITION : rapports périodiques, organes statutaires          │
└─────────────────────────────────────────────────────────────────┘

Transversal : Risques (COSO) · Audit interne (IGS) · GED · RACI ·
              Référentiels externes (ODD, Agenda 2063, sectoriels)
```

### 13.2 Briques techniques cibles

```
┌─────────────────────────────────────────────────────────────────┐
│ Frontend : React 19 + Inertia + Tailwind + shadcn + i18next     │
│   - Types stricts (depuis Resources Laravel)                     │
│   - Code splitting par route                                    │
│   - PWA-ready                                                   │
├─────────────────────────────────────────────────────────────────┤
│ Backend : Laravel 13 + PHP 8.3                                  │
│   - Form Requests partout                                       │
│   - Resources / API Resources                                   │
│   - State Machine (spatie/laravel-model-states)                │
│   - Queues Redis (jobs PDF, notifications, recalculs lourds)   │
│   - Cache Redis                                                 │
│   - Fortify + 2FA + Sanctum (API)                              │
├─────────────────────────────────────────────────────────────────┤
│ Données : MySQL 8 + Redis + Stockage S3-compatible PDF          │
│   - Chiffrement at-rest (LUKS / EBS)                            │
│   - Réplication master + 1 slave                                │
│   - Backups quotidiens + PITR                                   │
├─────────────────────────────────────────────────────────────────┤
│ Sécurité : Fortify 2FA + WAF + HTTPS forcé + CSP                │
│   - Vault / SOPS pour secrets                                   │
│   - Audit logs WORM (append-only, rétention 7 ans)              │
├─────────────────────────────────────────────────────────────────┤
│ Observabilité : Sentry + Prometheus + Grafana + Loki            │
│   - SLO/SLI définis (dispo 99,5 %, p95 < 1 s)                  │
│   - Alerting Slack / mail                                       │
├─────────────────────────────────────────────────────────────────┤
│ DevOps : GitHub Actions + Docker + Kubernetes (ou VM Laravel)   │
│   - 3 envs : dev / staging / prod                              │
│   - Déploiement bleu/vert ou rolling                            │
│   - Migrations automatiques + smoke tests                       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 14. ROADMAP DE CORRECTION (12 mois)

### Phase 0 — Quick Wins (S+0 à S+2)
- MFA, Form Requests, PHPStan/Pint/ESLint, sauvegarde BDD, headers sec, Sentry, typage TS.

### Phase 1 — Production-Ready (S+3 à S+10)
- Couverture tests 60 %+.
- Budget IPSAS (engagement→paiement).
- RGPD complet (registre, DPO, droits).
- CI/CD GitHub Actions.
- Monitoring + alerting.

### Phase 2 — Pilote Institutionnel (S+11 à S+22)
- Workflows validation hiérarchique.
- Organes statutaires.
- Évaluation RBM (mi-parcours / finale).
- Registre des risques.
- Module audit interne IGS.
- Multi-langues FR/EN.

### Phase 3 — V1 Stable (S+23 à S+38)
- Accessibilité WCAG 2.1 AA.
- Dashboard exécutif enrichi.
- Rapports manquants livrés.
- GED versionnée + classifiée.
- Multi-langues ES/PT.

### Phase 4 — V2 (S+39 à S+52)
- Signature électronique qualifiée.
- API open data.
- PWA.
- Cartographie.
- SSO institutionnel.

---

## 15. INDICATEURS DE SUIVI DE L'AUDIT

| KPI | Cible 3 mois | Cible 6 mois | Cible 12 mois |
|---|---|---|---|
| Couverture tests | 40 % | 65 % | 80 % |
| Constats P0 résolus | 100 % | – | – |
| Constats P1 résolus | – | 80 % | 100 % |
| MFA actif sur comptes sensibles | 100 % | – | – |
| Rapports incidents sécurité | < 2 / mois | < 1 / mois | < 0,5 / mois |
| Disponibilité applicative | 99,0 % | 99,3 % | 99,5 % |
| Temps réponse p95 | < 1,5 s | < 1,2 s | < 1 s |
| Conformité RGPD (auto-évaluation) | 60 % | 85 % | 100 % |
| Conformité ISO 27001 (auto-évaluation) | 40 % | 70 % | 90 % |
| Couverture WCAG 2.1 AA | – | 70 % | 100 % |

---

## 16. CONCLUSION

L'application **TB-PAPA-CEEAC** présente un socle architectural et fonctionnel **de qualité institutionnelle** sur les fondamentaux : chaîne RBM officielle correctement implémentée, moteur de reporting documentaire institutionnel solide (QR + SHA-256 + audit trail), RBAC riche, codification automatique et propagation cascade fonctionnelles.

Sa montée en production institutionnelle complète nécessite cependant un effort structuré sur **trois axes critiques** :

1. **Renforcement sécurité & conformité** (MFA, RGPD, ISO 27001, chiffrement).
2. **Élévation drastique de la couverture tests** (< 10 % → > 60 %).
3. **Industrialisation de l'exploitation** (CI/CD, monitoring, sauvegardes, runbook).

À cela s'ajoutent **deux extensions fonctionnelles majeures** :
- Le **cycle budgétaire IPSAS** (engagement → liquidation → ordonnancement → paiement).
- Les **workflows de validation hiérarchique** institutionnels.

Avec une roadmap de 12 mois structurée en 4 phases (Quick Wins → Production-Ready → Pilote → V1 Stable), TB-PAPA-CEEAC peut devenir une **plateforme de référence** pour la gestion axée sur les résultats au sein de la Commission de la CEEAC et, par capillarité, des autres Communautés Économiques Régionales africaines.

---

**Fin de l'audit** — version 1.0 — 27 mai 2026
*Audit non destructif : aucun code applicatif n'a été modifié lors de cette revue. Les constats sont à transformer en tickets de backlog institutionnel.*
