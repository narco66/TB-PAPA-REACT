# Workflow de validation hiérarchique — TB-PAPA-CEEAC

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre les constats C-007 (workflows non modélisés) et C-012 (visa électronique) de [docs/AUDIT-COMPLET.md](AUDIT-COMPLET.md).**

---

## 1. Vue d'ensemble

Le workflow de validation institutionnelle est piloté par un **moteur d'états centralisé** (`WorkflowService`) qui :
- Définit les transitions autorisées par type de ressource (PAPA aujourd'hui, Axes/Activités à venir)
- Vérifie la **permission** spécifique à chaque transition
- Persiste chaque transition dans `validations` (audit trail + visa électronique)
- Déclenche des **notifications** vers les auditeurs et le SG (DB + email)
- Expose à l'UI la liste des transitions disponibles + l'historique

Aucune dépendance externe (state-machine package). Implémentation directe, auditable, testable.

---

## 2. Matrice de transitions PAPA

Définie dans `App\Services\Workflow\WorkflowService::MATRICE_PAPA`.

```
brouillon ──submit──► en_validation ──approve──► valide ──close──► cloture ──archive──► archive
                            │                       │
                            └──reject──► brouillon  └──revise──► revise ──resubmit──► en_validation
```

| Depuis | Action | Vers | Permission | Étape | Décision |
|---|---|---|---|---|---|
| brouillon | submit | en_validation | `papa.submit` | soumission | approuve |
| en_validation | approve | valide | `papa.validate` | validation_presidence | approuve |
| en_validation | reject | brouillon | `papa.validate` | validation_presidence | **rejete** |
| valide | revise | revise | `papa.revise` | revue_technique | approuve |
| valide | close | cloture | `papa.close` | cloture | approuve |
| revise | resubmit | en_validation | `papa.submit` | soumission | approuve |
| cloture | archive | archive | `papa.archive` | cloture | approuve |

**Effets de bord automatiques** :
- Transition vers `valide` → `date_validation` + `valide_par_id` renseignés
- Transition vers `cloture` → `cloture_le` + `cloture_par_id` + `verrouille = true`
- Transition vers `archive` → `verrouille = true`

---

## 3. Permissions par rôle institutionnel

| Permission | Rôles habilités |
|---|---|
| `papa.create` | admin_technique, admin_fonctionnel |
| `papa.submit` | secretaire_general, admin_technique |
| `papa.validate` | president, admin_technique |
| `papa.revise` | secretaire_general, admin_technique |
| `papa.close` | secretaire_general, admin_technique |
| `papa.archive` | president, admin_technique |

Modifier la matrice → éditer [database/seeders/RolesPermissionsSeeder.php](database/seeders/RolesPermissionsSeeder.php) puis `php artisan db:seed --class=RolesPermissionsSeeder`.

---

## 4. Schéma BDD — table `validations`

Polymorphe (`validable_type`, `validable_id`), permettant d'étendre le workflow à toute ressource sans nouvelle table.

| Colonne | Type | Rôle |
|---|---|---|
| `validable_type` / `validable_id` | morphs | Cible polymorphe (Papa, Axe, Activite…) |
| `etape` | enum | soumission / revue_technique / validation_directeur / commissaire / sg / presidence / cloture |
| `decision` | enum | en_attente / approuve / rejete / renvoye |
| `demandeur_id`, `valideur_id` | FK users | Acteurs |
| `decide_at` | timestamp | Horodatage du visa |
| `commentaire` | text | Justification métier |
| `donnees_avant`, `donnees_apres` | JSON | Snapshot pour audit (statut + tout champ pertinent) |

**Visa électronique** : chaque ligne `validations` constitue une signature horodatée, immuable (pas d'`update`), attachée à un utilisateur authentifié — équivalent fonctionnel d'un visa institutionnel.

---

## 5. API HTTP

### 5.1 Route unique de transition

```
POST /papa/{papa}/workflow/{action}
```

Où `action` ∈ `{submit, resubmit, approve, reject, revise, close, archive}`.

**Corps** :
```json
{ "commentaire": "Texte libre, ≤ 2000 caractères, optionnel sauf pour reject (recommandé)" }
```

**Réponses** :
- 302 (back) + flash `success` → transition effectuée
- 302 (back) + `errors.workflow` → transition refusée (statut interdit ou permission manquante)

### 5.2 Lecture des transitions disponibles

`GET /papa/{papa}` (Inertia, page `papa/show`) reçoit :
- `transitions` : array de `{key, libelle, to, permission_ok, permission}`
- `historique` : chronologie des validations (avec `demandeur`, `valideur`, `commentaire`, `avant/apres`)

---

## 6. Notifications

Classe : `App\Notifications\WorkflowTransitionNotification` — canaux `database` + `mail`.

À chaque transition, les utilisateurs des rôles suivants reçoivent une notification (à l'exception de l'auteur) :
- `audit_interne`
- `controle_financier`
- `secretaire_general`

**Stockage** : table `notifications` (Laravel standard, type+notifiable polymorphes). Le front pourra à terme afficher un compteur cloche dans la sidebar.

**Email** : nécessite un MAIL_MAILER configuré (`smtp`, `mailgun`, `ses`…). Tant que le mail n'est pas configuré, les notifications restent en BDD.

---

## 7. Composants React

### 7.1 `<WorkflowTimeline historique={...} />`

Affiche l'historique chronologique sous forme de timeline verticale avec :
- Icône colorée par décision (approuvé=vert, rejeté=rouge, en attente=ambre, renvoyé=bleu)
- Étape + transition statut (avant → après)
- Acteur + horodatage relatif et absolu
- Commentaire encadré

### 7.2 `<WorkflowActions transitions={...} onTransition={...} />`

- Boutons pour chaque transition disponible
- Désactivés grisés si l'utilisateur n'a pas la permission (titre = permission requise)
- Click → ouvre un formulaire commentaire (Textarea + valider/annuler)
- Variante "destructive" pour `reject`

Localisation : [resources/js/components/workflow/workflow-timeline.tsx](resources/js/components/workflow/workflow-timeline.tsx).

---

## 8. Tests

Suite : `tests/Feature/Papa/PapaWorkflowTest.php` (7 cas) :

| # | Test |
|---|---|
| 1 | `submit` crée une entrée validation avec commentaire et passe en `en_validation` |
| 2 | `reject` renvoie au statut brouillon + décision `rejete` enregistrée avec motif |
| 3 | Transition interdite depuis l'état courant → erreur workflow (statut inchangé) |
| 4 | Cycle complet brouillon → en_validation → valide → revise → resubmit → valide (5 validations en BDD) |
| 5 | archive après cloture verrouille définitivement (verrouille=true) |
| 6 | `papa/show` reçoit `transitions` + `historique` dans les props Inertia |
| 7 | Utilisateur sans permission (point_focal) ne peut pas transitionner |

Plus le test existant `PapaControllerTest::test_workflow_soumission_validation_cloture` qui utilise désormais la nouvelle route.

---

## 9. Extension à Axe, Activité, Indicateur

Pour ajouter une ressource au workflow :

1. **Définir une matrice** dans `WorkflowService` :
   ```php
   public const MATRICE_AXE = [
       'brouillon' => ['submit' => ['to' => 'soumis', 'etape' => 'soumission', 'permission' => 'validate_axes', 'libelle' => 'Soumettre']],
       // ...
   ];
   ```

2. **Mettre à jour `matricePour()`** :
   ```php
   match ($entite::class) {
       Papa::class => self::MATRICE_PAPA,
       Axe::class => self::MATRICE_AXE,
       // ...
   };
   ```

3. **Ajouter une route + endpoint** dans le contrôleur de la ressource (mêmes appels au service).

4. **Réutiliser** `<WorkflowTimeline>` et `<WorkflowActions>` côté React.

---

## 10. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **ISO 9001 § 8.5** | Contrôle des changements documentés | ✅ validations + commentaire + snapshot |
| **COSO ERM** | Séparation des tâches (auteur ≠ valideur) | 🟡 partiel (à durcir avec règle « demandeur ≠ valideur ») |
| **CDC § 5.4** | Workflow validation hiérarchique | ✅ matrice + visa + audit |
| **RGAA institutionnel** | Traçabilité décisionnelle | ✅ historique + horodatage + acteur |
| **CEEAC RACI § 4.4** | Acteurs par étape | ✅ permission par transition |

---

## 11. Chantiers d'extension

| Priorité | Action | Effort |
|---|---|---|
| P1 | Étendre le workflow à Axe + Activité | M |
| P1 | Règle « demandeur ≠ valideur » (séparation des tâches COSO) | S |
| P1 | Délai d'instruction (alerte si en_validation > N jours) | S |
| P2 | Workflow multi-étapes (commissaire → SG → présidence) | M |
| P2 | Délégation temporaire (en cas d'absence) | M |
| P2 | UI : compteur cloche notifications dans sidebar | S |
| P3 | Signature qualifiée eIDAS sur visa | L |

---

**Fin du document** — version 1.0
