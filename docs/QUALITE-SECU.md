# Stack qualité & sécurité — TB-PAPA-CEEAC

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre les Quick Wins identifiés dans `docs/AUDIT-COMPLET.md` (§12.1).**

---

## 1. Installation initiale

Après `git pull`, exécuter :

```bash
composer install
npm install
```

Cela installe :
- **Larastan 3.x** (PHPStan pour Laravel) — analyse statique niveau 5
- **Laravel Pint 1.x** — formateur PSR-12 + préset Laravel
- **ESLint 9.x** + **typescript-eslint** + **eslint-plugin-react** + **react-hooks**
- **Prettier 3.x** + **eslint-config-prettier**

> 💡 **Backup** : implémenté via commande artisan custom `app:backup` (pas de dépendance externe). Voir §2.3. La librairie `spatie/laravel-backup` n'est pas encore compatible Laravel 13.

---

## 2. Outils et commandes

### 2.1 PHP — qualité de code

| Commande | Effet |
|---|---|
| `composer lint` | Reformate le code PHP (Pint, préset Laravel). **Modifie les fichiers.** |
| `composer lint:check` | Vérifie sans modifier. À utiliser en CI. |
| `composer stan` | Analyse statique Larastan niveau 5. |
| `composer test` | Exécute la suite PHPUnit. |
| `composer quality` | Lance lint:check + stan + test (toute la chaîne). |

Configuration :
- `pint.json` — règles Pint (single quote, ordered imports, trailing comma).
- `phpstan.neon` — paths analysés, niveau 5, exclusions.

### 2.2 Front — qualité de code

| Commande | Effet |
|---|---|
| `npm run lint` | ESLint sur `resources/js/**/*.{ts,tsx}`. |
| `npm run lint:fix` | Corrige automatiquement ce qui peut l'être. |
| `npm run format` | Prettier — reformate. |
| `npm run format:check` | Prettier — vérifie. |
| `npm run typecheck` | `tsc --noEmit` — vérifie les types sans build. |
| `npm run build` | Build Vite production. |
| `npm run dev` | Vite dev server. |

Configuration :
- `eslint.config.js` — config flat ESLint 9.
- `.prettierrc.json` — règles de format.
- `.prettierignore` — exclusions.

### 2.3 Sauvegardes BDD

Implémentée via une commande artisan custom (sans dépendance externe), localisée dans
[app/Console/Commands/BackupCommand.php](app/Console/Commands/BackupCommand.php).

| Commande | Effet |
|---|---|
| `php artisan app:backup` | Dump MySQL + storage privé + .env dans une archive zip horodatée. |
| `php artisan app:backup --only-db` | BDD seule (sans fichiers). |
| `php artisan app:backup --keep=60` | Conserve 60 jours d'archives (défaut : 30). |
| `composer backup` | Raccourci `php artisan app:backup`. |

**Logique du backup** :
- Dump MySQL via `mysqldump` (résolution automatique du chemin Laragon / XAMPP / /usr/bin).
- Options dump : `--single-transaction --quick --routines --triggers --default-character-set=utf8mb4`.
- Archive ZIP horodatée dans `storage/app/backups/tb-papa-YYYY-MM-DD_HHMMSS.zip`.
- Nettoyage automatique des archives plus anciennes que `--keep` jours.

**Planification automatique** (`routes/console.php`) :
- 02:00 — `app:backup --keep=30`

> ⚠️ Pour la conformité IPSAS / ISO 27001 (rétention 7 ans), prévoir un job hebdomadaire qui copie l'archive sur un stockage offsite (S3, NAS, FTP) avec sa propre stratégie de rétention long terme.

⚠️ **Le scheduler Laravel doit tourner.** Sur Windows / Laragon, créer une tâche planifiée :

```powershell
schtasks /Create /SC MINUTE /MO 1 /TN "TB-PAPA Scheduler" `
  /TR "C:\laragon\bin\php\php-8.3\php.exe c:\laragon\www\TB-PAPA-REACT\artisan schedule:run" `
  /RU SYSTEM /F
```

Sur Linux/macOS, ajouter au crontab :

```cron
* * * * * cd /chemin/vers/tb-papa-react && php artisan schedule:run >> /dev/null 2>&1
```

**Variables `.env` à définir** (optionnel, pour notifications) :

```
MAIL_FROM_ADDRESS=no-reply@ceeac-eccas.org
MAIL_FROM_NAME="TB-PAPA-CEEAC"
```

---

## 3. Sécurité applicative

### 3.1 Middleware `SecurityHeaders`

Localisation : `app/Http/Middleware/SecurityHeaders.php`
Enregistré dans `bootstrap/app.php` sur tout le groupe `web`.

Headers ajoutés à toute réponse :

| Header | Valeur | Effet |
|---|---|---|
| `X-Content-Type-Options` | `nosniff` | Empêche le MIME sniffing. |
| `X-Frame-Options` | `SAMEORIGIN` | Anti-clickjacking. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limite la fuite d'URL via Referer. |
| `X-Permitted-Cross-Domain-Policies` | `none` | Bloque Flash/PDF cross-domain. |
| `Permissions-Policy` | Désactive camera, micro, géoloc, USB, paiement, FLoC. | Réduit la surface API navigateur. |
| `Cross-Origin-Opener-Policy` | `same-origin` | Isole le navigation context. |
| `Cross-Origin-Resource-Policy` | `same-origin` | Bloque le chargement de ressources cross-origin. |
| `Strict-Transport-Security` *(prod)* | 2 ans, includeSubDomains, preload | Force HTTPS — **uniquement en prod**. |
| `Content-Security-Policy` | Voir ci-dessous. | Limite scripts, styles, images, etc. |

### 3.2 Politique CSP

CSP différenciée dev / prod :
- **dev** : autorise `unsafe-eval`, websocket Vite, `localhost:*` pour HMR.
- **prod** : `'self' 'unsafe-inline'` pour les scripts, websocket interdits.

Pour ajouter un CDN (ex. fonts Google → fonts Bunny déjà autorisé), modifier `buildCsp()` dans le middleware.

### 3.3 Validation : Form Requests

Toutes les écritures (`store`, `update`) des contrôleurs RBM / Activités / Indicateurs utilisent désormais des Form Requests dédiées avec :
- `authorize()` qui rejoue la policy
- `rules()` strictement typé
- `messages()` quand un message métier est requis

Localisations :
- `app/Http/Requests/Rbm/` — Axe / Produit / SousProduit / Tache (Store + Update)
- `app/Http/Requests/Activite/` — StoreActivite / UpdateAvancement
- `app/Http/Requests/Indicateur/` — StoreIndicateur / StoreValeurIndicateur

Bénéfice : double protection (policy + validation), réduction du code des contrôleurs, testabilité accrue.

---

## 4. Workflow de développement recommandé

### 4.1 Avant chaque commit

```bash
composer lint          # reformate PHP
npm run format         # reformate JS/TS
npm run lint:fix       # corrige ESLint automatiquement
composer quality       # lance pint:check + phpstan + phpunit
npm run typecheck      # vérifie les types
npm run build          # vérifie que le bundle compile
```

### 4.2 En CI (à mettre en place — chantier P0)

Pipeline GitHub Actions recommandé (`.github/workflows/quality.yml`, à créer) :

```yaml
- composer install --no-progress --prefer-dist
- npm ci
- composer lint:check
- composer stan
- composer test
- npm run lint
- npm run format:check
- npm run typecheck
- npm run build
```

### 4.3 Niveaux d'évolution PHPStan

Actuel : **niveau 5**. Trajectoire conseillée :
- Niveau 6 dès que `mixed` est éliminé des modèles
- Niveau 7 après typage strict des relations Eloquent
- Niveau 8 (max) après refonte des `null safety`

Pour monter d'un niveau : `level: 6` dans `phpstan.neon`, lancer, corriger, valider.

---

## 5. Constats P0/P1 couverts par ces Quick Wins

| Constat audit | État avant | État après |
|---|---|---|
| C-002 Couverture tests < 10 % | 12 tests / 40 assertions | **80 tests / 234 assertions** (~6×) |
| C-001 MFA absent (rôles sensibles) | Aucune 2FA | Google2FA TOTP + 8 rôles sensibles enrôlés |
| C-013 Types React `any` partout | Aucun typage métier | Types complets `resources/js/types/index.ts` |
| C-019 Tests Policies + Form Requests | Validation inline | Form Requests dédiées + tests Feature |
| C-024 PHPStan / Pint / ESLint non configurés | Aucun outil | Stack complète configurée |
| C-006 Sauvegarde BDD automatisée absente | Aucune | Commande `app:backup` + scheduler quotidien |
| OWASP A05 Security Misconfiguration | Headers minimaux | 9 headers défensifs + CSP |
| OWASP A07 Authentication Failures | Pas de MFA, password min 8 | TOTP + verrouillage + StrongPassword (12+) |

---

## 6. Chantiers restants (hors Quick Wins)

Voir `docs/AUDIT-COMPLET.md` §12 pour la liste exhaustive. Prochaines priorités :

| Priorité | Chantier | Effort | État |
|---|---|---|---|
| ~~P0~~ | ~~MFA pour rôles sensibles~~ | S | ✅ Fait |
| ~~P0~~ | ~~Couverture tests~~ | M | ✅ Fait (80 tests) |
| P0 | Cycle budgétaire IPSAS | L | Prochain |
| P0 | CI/CD GitHub Actions | M | Suivant |
| P1 | Workflows validation hiérarchique | M | À planifier |
| P1 | Évaluation RBM mi-parcours / finale | M | À planifier |
| P1 | Registre RGPD + DPO | S | À planifier |
| P1 | Indicateurs CMR complets (baseline/cible/paliers) | S | À planifier |

---

**Fin du document** — version 1.0
