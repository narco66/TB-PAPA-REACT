# Chaîne de la dépense — Guide utilisateur

**TB-PAPA-CEEAC** · Guide pratique pour les agents de la Commission.

---

## 1. À qui s'adresse ce guide

Ce guide accompagne les utilisateurs de l'application **TB-PAPA-CEEAC** dans la gestion quotidienne des dépenses institutionnelles, du besoin exprimé au paiement effectué.

### Qui fait quoi

| Rôle | Mission | Permissions |
| --- | --- | --- |
| **Point focal opérationnel** | Initie les expressions du besoin pour son activité | Créer, soumettre, annuler |
| **Chef de service** | Initie et soumet les expressions de son service | Créer, soumettre, annuler |
| **Directeur technique / d'appui** | Valide hiérarchiquement, peut engager | Valider, rejeter, engager, gérer fournisseurs |
| **Commissaire** | Valide les expressions de son département | Valider, rejeter, engager |
| **Secrétaire Général** | Coordination, supervise les validations | Valider, rejeter, engager |
| **Contrôleur financier** | Visa préalable, contrôle disponibilité | Lire, valider |
| **Auditeur interne** | Lecture pour audits ex-post | Lecture seule |
| **Administrateur** | Configuration globale | Toutes |

---

## 2. Comment accéder au module

Depuis le menu de gauche, section **Chaîne de la dépense** :

- 📊 **Tableau de bord dépense** — Vue d'ensemble (KPI, cycle IPSAS, retards)
- 📝 **Expressions du besoin** — Créer et suivre les demandes
- ✅ **Service fait & Réception** — Constater l'exécution
- 🏢 **Fournisseurs** — Référentiel des fournisseurs institutionnels

URL directe : `http://{votre-domaine}/expense`

---

## 3. Étape 1 — Créer une expression du besoin

### Quand ?
Dès qu'un besoin est identifié (matériel, mission, prestation, formation, etc.) et **avant tout engagement** de dépense.

### Comment ?

1. Aller sur **« Expressions du besoin »** dans le menu
2. Cliquer sur **« Nouvelle expression »** (bouton bleu en haut à droite)
3. Remplir le formulaire :

| Champ | Conseil |
| --- | --- |
| **Exercice** | Choisir l'exercice budgétaire en cours (statut « validé ») |
| **Type d'engagement** | Choisir parmi les 18 types (achat de biens, prestation, mission, formation…) |
| **Priorité** | 1 = Urgente, 2 = Haute, 3 = Normale, 4 = Basse |
| **Objet** | Description courte et claire (255 caractères max) |
| **Justification** | Pourquoi cette dépense est nécessaire — soyez précis |
| **Description détaillée** | Détails techniques, références, contexte |
| **Département/Direction** | Rattachement institutionnel |
| **Activité/Tâche RBM** | Lien vers le plan d'action stratégique (PAPA) |
| **Montant estimé** | TTC, devise XAF par défaut |
| **Part CEEAC / Part PTF** | Si financement mixte. Règle : **Total = CEEAC + PTF** |
| **Source de financement** | CEEAC-EM, UE, BAD, BM, ONUDI, etc. |
| **Fournisseur pressenti** | Optionnel à ce stade — peut être modifié plus tard |
| **Date besoin / livraison** | Calendrier prévisionnel |

4. Cliquer sur **« Créer (brouillon) »**

> 💡 L'expression est enregistrée en brouillon. Vous pouvez la modifier autant que nécessaire avant de la soumettre.

### Le numéro automatique

Chaque expression reçoit un numéro unique au format **`EB-2026-00001`** (Expression de Besoin, année, séquentiel sur 5 chiffres).

---

## 4. Étape 2 — Soumettre pour validation

Une fois votre brouillon prêt :

1. Ouvrir l'expression (clic sur l'objet ou le numéro)
2. Cliquer sur **« Soumettre pour validation »**

Votre demande passe au statut **« Soumis »** et votre responsable hiérarchique reçoit une **notification automatique**.

### Que se passe-t-il ensuite ?

Trois scénarios possibles côté validateur :

1. ✅ **Validation** — Votre demande est acceptée, statut → `valide`, notification au demandeur
2. ↩️ **Retour pour correction** — Vous devez modifier et resoumettre, statut → `retourne_correction`
3. ❌ **Rejet** — Refus définitif avec motif, statut → `rejete`

Dans tous les cas, vous recevez une notification avec le motif de la décision.

---

## 5. Étape 3 — Validation hiérarchique (Directeurs / Commissaires)

### Pour les valideurs

1. Vous recevez une notification : *« Expression du besoin soumise »*
2. Aller sur l'expression depuis la notification (ou directement depuis la liste)
3. Analyser : opportunité, montant, imputation budgétaire, justification
4. Cliquer sur l'une des actions :
   - **« Valider »** (sans motif requis, ou avec commentaire facultatif)
   - **« Retour pour correction »** (motif **obligatoire**)
   - **« Rejeter »** (motif **obligatoire**)

> 📄 Un PDF de **« Visa accordé »** ou **« Notification de rejet/retour »** est généré automatiquement, archivé et téléchargeable.

---

## 6. Étape 4 — Demande de visa financier (optionnel)

Pour certains engagements importants, un visa préalable du Contrôle financier est requis :

1. Sur l'expression validée, cliquer sur **« Demande visa »** dans le bandeau d'actions
2. Un PDF officiel est généré, à transmettre au Contrôleur financier
3. Le visa est matérialisé par le PDF **« Visa financier »** signé numériquement

---

## 7. Étape 5 — Engagement budgétaire

Une expression validée doit être **engagée** pour réserver effectivement le budget.

### Pour les ordonnateurs (Directeurs, Commissaires, SG)

1. Ouvrir l'expression au statut `valide`
2. Faire défiler jusqu'à **« Engager budgétairement »**
3. Renseigner les **imputations** (multi-imputation possible) :
   - **Ligne budgétaire** : ID de la ligne (visible dans le module Budget)
   - **Libellé** : description de l'imputation
   - **Montant** : montant à imputer sur cette ligne
4. Cliquer sur **« + Ajouter une imputation »** pour ventiler sur plusieurs lignes
5. Cliquer sur **« Engager le budget »**

> ⚠️ Si le montant total dépasse le disponible budgétaire, l'engagement est **automatiquement refusé** (anti-dépassement).

### Que se passe-t-il ?

- Un **BudgetMouvement de type `engagement`** est créé
- La ligne budgétaire voit son montant engagé augmenter
- L'expression passe au statut `engage`
- Le **PDF « Bon d'engagement »** est disponible (bouton en haut)
- Le demandeur, le contrôle financier et le valideur sont notifiés

---

## 8. Étape 6 — Service fait & Réception

Après l'exécution de la prestation par le fournisseur, **vous devez constater le service fait**.

### Constater le service fait (prestations)

1. Aller dans **« Service fait & Réception »**
2. Cliquer sur **« Nouveau certificat »**
3. Remplir :
   - **Mouvement engagement (ID)** : ID du BudgetMouvement d'engagement
   - **Date de constatation**
   - **Description** : ce qui a été fait
   - **Montant constaté** : ≤ montant engagé
   - **Conformité qualitative** : conforme / partiellement / non conforme
   - **Conformité quantitative** : idem
   - **Observations**
4. Valider le formulaire

Un certificat **`SF-2026-00001`** est créé en statut `projet`.

### Valider le certificat

Le supérieur hiérarchique clique sur **« Valider »** → statut `valide`.

> 📄 **PDF « Certificat de service fait »** disponible immédiatement.

### Procès-verbal de réception (biens, services, travaux)

Pour les **biens, services ou travaux**, un PV de réception est obligatoire avec une **commission de 3 membres** :

1. **« Nouveau PV »**
2. Renseigner :
   - Date, type (provisoire/définitive/partielle/refus), nature (biens/services/travaux)
   - Quantité reçue, unité de mesure (ex: 5, "unités")
   - Montant reçu, conformité
   - Réserves (si conformité partielle)
   - **Président de commission**, **Membre 1**, **Membre 2** (IDs utilisateurs)
3. Soumettre

Un PV **`PV-2026-00001`** est créé. Le supérieur le valide ensuite.

> 📄 **PDF « Procès-verbal de réception »** avec signatures des 3 membres de la commission.

---

## 9. Étape 7 — Liquidation détaillée

Une fois le service fait constaté, la liquidation transforme l'engagement en dette exigible.

### Cas standard

La liquidation simple se fait via le module Budget existant (`BudgetCycleService`). Elle est plafonnée par l'engagement.

### Cas avec retenues / pénalités

Pour les marchés avec **retenue de garantie, retenues fiscales, pénalités de retard, avances** :

1. Aller dans la liquidation créée
2. Renseigner un **« Détail de liquidation »** :

| Élément | Description |
| --- | --- |
| Montant brut | Total liquidé avant retenues |
| Avance déduite | Avance déjà versée à déduire |
| Retenue de garantie | Caution conservée (ex : 5 % du marché) |
| Retenue fiscale | IRPP, TVA, autres impôts |
| Autres retenues | Compensation, etc. |
| Pénalités de retard | Si livraison hors délai |
| Autres pénalités | Pénalités contractuelles |

Le **montant net à payer** est calculé automatiquement.

> 📄 **PDF « Fiche de liquidation »** + **« Décompte de paiement »** disponibles.

---

## 10. Étape 8 — Ordonnancement

L'ordonnateur émet l'ordre de payer (via le module Budget — `BudgetCycleService::ordonnancer`).

- **PDF « Ordonnance de paiement »** généré pour chaque ordre
- **PDF « Bordereau d'ordonnancement »** pour regrouper plusieurs ordonnances sur une période (transmission au comptable)
- Notifications automatiques au Comptable et au Contrôle financier

---

## 11. Étape 9 — Paiement

Le Comptable exécute le paiement (`BudgetCycleService::payer`) :

- Sélectionne le mode (virement, chèque, espèces, mobile money)
- Renseigne la pièce comptable, le compte bancaire, la date de valeur
- Le système refuse si le **comptable est l'ordonnateur** (séparation COSO ERM)

Une fois payé, deux documents officiels sont disponibles :
- **PDF « Avis de paiement »** — Confirmation envoyée au bénéficiaire
- **PDF « Reçu de paiement (quittance) »** — Document officiel acquittant la dette

Le demandeur, l'ordonnateur et le valideur sont notifiés.

---

## 12. Cas pratiques

### Cas 1 : Achat de fournitures de bureau

```
1. Point focal → Crée expression "Achat papier A4 5 ramettes"
                 Type: achat_biens, Montant: 50 000 XAF
2. Soumet pour validation
3. Directeur → Valide
4. Directeur → Engage sur ligne 60111 "Fournitures de bureau"
5. Réception fournitures → PV de réception (commission 3 membres)
6. Service fait constaté → Certificat SF
7. Liquidation 50 000 XAF
8. Ordonnancement
9. Paiement par virement bancaire au fournisseur
10. Quittance remise au fournisseur
```

### Cas 2 : Mission officielle d'un Commissaire

```
1. Secrétariat → Crée expression "Mission Addis-Abeba — Réunion UA"
                 Type: mission_officielle, Montant: 1 200 000 XAF
                 Part CEEAC: 1 200 000 (100%)
2. SG → Valide
3. SG → Engage sur ligne mission internationale
4. Mission effectuée
5. Service fait constaté avec rapport de mission
6. Liquidation avec retenue fiscale (IRPP sur indemnités)
7. Ordonnancement → Paiement perdiem
```

### Cas 3 : Marché de travaux avec retenue de garantie

```
1. Direction Infrastructures → Crée expression "Réfection 3e étage siège"
                                Type: travaux, Montant: 25 000 000 XAF
2. Commissaire → Valide
3. PV de réception définitive après livraison
4. Liquidation détaillée :
   - Brut: 25 000 000
   - Retenue garantie 5%: -1 250 000
   - Retenue fiscale 10%: -2 500 000
   - Net à payer: 21 250 000
5. Ordonnancement
6. Paiement
7. Quittance
(La retenue de garantie sera libérée à la fin de la garantie contractuelle)
```

---

## 13. Tableau de bord — Pilotage quotidien

Le **Tableau de bord dépense** (`/expense`) propose en temps réel :

- **KPI** : Expressions totales, montants engagés/payés, taux de paiement, retards
- **Cycle IPSAS** : Graphique des 4 étapes (engagement → paiement)
- **Expressions par statut** : Pie chart
- **Évolution mensuelle** : Line chart engagements vs paiements cumulés
- **Top fournisseurs** : Classement par montant
- **Files d'attente** :
  - Expressions en attente de validation
  - Certificats SF à valider
  - PV de réception à valider

### Export en un clic

Tous les écrans proposent des boutons :
- 📄 **PDF** — Documents institutionnels signés (logo CEEAC, QR vérification)
- 📊 **Excel/CSV** — Journaux opérationnels pour analyse ou archivage

---

## 14. Notifications

Vous recevez automatiquement une notification quand :

| Événement | Concerné |
| --- | --- |
| Une expression est soumise | Directeurs, Commissaires |
| Validation est attendue | Valideurs hiérarchiques |
| Votre expression est validée | Demandeur |
| Votre expression est rejetée (avec motif) | Demandeur |
| Votre expression est retournée pour correction | Demandeur |
| Votre expression est engagée | Demandeur + CF |
| Visa financier accordé | Demandeur |
| Service fait attendu | Contrôle financier |
| Liquidation validée | Contrôle financier |
| Ordonnancement généré | Comptable, CF |
| Paiement effectué | Demandeur, Ordonnateur |
| Échéance dépassée | Directeurs, Commissaires |

Toutes les notifications sont consultables dans la cloche en haut à droite (système Laravel natif).

---

## 15. Conseils & bonnes pratiques

✅ **À faire**
- Bien remplir la justification — elle conditionne souvent l'acceptation
- Vérifier la cohérence Total = CEEAC + PTF avant soumission
- Pré-sélectionner un fournisseur si déjà connu (facilite l'engagement)
- Joindre les pièces justificatives en GED
- Garder à jour le référentiel fournisseurs

❌ **À éviter**
- Soumettre une expression sans budget disponible (refus automatique à l'engagement)
- Demander une liquidation supérieure à l'engagement (refus automatique)
- Confondre `partenaire` (financier PTF) et `supplier` (fournisseur commercial)
- Multiplier les expressions pour une même dépense — préférer la multi-imputation

---

## 16. Foire aux questions

**Q : Mon expression est en brouillon depuis 1 semaine, est-elle visible par mon directeur ?**
R : Non. Tant que vous ne soumettez pas, seul vous voyez le brouillon. Cliquez sur « Soumettre pour validation » pour la rendre visible.

**Q : Comment annuler une expression engagée ?**
R : Impossible directement — il faut une procédure d'annulation budgétaire (contact admin financier). Les engagements représentent des montants réservés sur le budget.

**Q : Le PDF de mon visa n'apparaît pas. Pourquoi ?**
R : Vérifiez le statut de l'expression : seules les expressions validées (`valide` ou `engage`) génèrent le PDF « Visa financier ».

**Q : Puis-je créer une expression pour un exercice budgétaire futur ?**
R : Oui, tant que l'exercice existe dans le système (même au statut « brouillon »). Mais l'engagement sera bloqué tant que l'exercice n'est pas « validé ».

**Q : Comment exporter mes expressions en Excel ?**
R : Depuis la liste des expressions, cliquez sur le bouton **« Exporter »** en haut à droite et choisissez Excel (.xlsx) ou CSV. Les filtres actifs sont préservés.

**Q : Je suis Comptable mais je ne peux pas engager. Pourquoi ?**
R : Conformément à la règle COSO ERM, un Comptable ne peut pas être Ordonnateur. Vous payez les ordonnancements émis par d'autres.

**Q : Le système refuse mon paiement. Que faire ?**
R : Vérifiez que vous n'êtes pas l'ordonnateur qui a émis l'ordonnance. La séparation des fonctions est obligatoire.

---

## 17. Support

Pour toute question ou anomalie :

- **Support fonctionnel** : Direction des Affaires Financières
- **Support technique** : Direction des Systèmes d'Information (DSI CEEAC)
- **Email** : `dsi@ceeac.org` (placeholder — à adapter)

**Documentation technique complète** : `docs/CHAINE-DEPENSE-TECHNIQUE.md`

---

**Version du guide : 1.0** · Dernière mise à jour : Phase 7 du chantier Chaîne de la dépense · TB-PAPA-CEEAC
