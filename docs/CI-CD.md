# CI/CD — TB-PAPA-CEEAC

**Version** : 1.0 · **Date** : 27 mai 2026
**Couvre les constats C-004 (CI/CD absent) et C-024 (qualité automatisée) de [docs/AUDIT-COMPLET.md](AUDIT-COMPLET.md).**

---

## 1. Vue d'ensemble

L'intégration continue est portée par **GitHub Actions**. Chaque `push` sur `main` / `develop` et chaque `pull_request` déclenche automatiquement une chaîne de validation qualité.

**Aucune dépendance externe payante** : 100 % runners GitHub-hosted (`ubuntu-latest`), 100 % open-source.

---

## 2. Workflows

### 2.1 `quality.yml` — Pipeline principal

Déclencheurs : `push` (main, develop), `pull_request` (main, develop), `workflow_dispatch` (manuel).

**Job backend (PHP 8.3)** :
1. Setup PHP 8.3 + extensions (mbstring, pdo_sqlite, intl, gd, zip, bcmath…)
2. Cache des dépendances Composer
3. `composer install --prefer-dist --no-progress --optimize-autoloader`
4. Préparation env (.env, key:generate, SQLite touch)
5. **Pint** (`vendor/bin/pint --test`) — strict
6. **PHPStan / Larastan** niveau 5 (`vendor/bin/phpstan analyse`) — strict, format GitHub
7. **PHPUnit** (`php artisan test`) — strict, 97 tests, SQLite in-memory

**Job frontend (Node 22)** :
1. Setup Node 22 + cache npm
2. `npm ci --no-audit --no-fund`
3. **ESLint** (`npm run lint`) — `continue-on-error` (élévation strict prévue)
4. **Prettier check** (`npm run format:check`) — `continue-on-error`
5. **TypeScript** (`npm run typecheck`) — `continue-on-error`
6. **Vite build** (`npm run build`) — **strict** (must pass)

Concurrence : `cancel-in-progress` activé — un nouveau push annule les jobs en cours sur la même branche.

Timeout : 15 min backend, 10 min frontend.

### 2.2 `security.yml` — Audit dépendances

Déclencheurs : tous les **lundis 06:00 UTC** (cron), `push` sur `composer.lock` ou `package-lock.json`, `workflow_dispatch`.

**Composer audit** (strict) — détecte les CVE sur dépendances PHP via la base advisory.
**NPM audit** (modérées et plus, continue-on-error) — détecte les CVE JS.

### 2.3 `dependabot.yml` — Mises à jour automatiques

| Ecosystème | Fréquence | Groupes |
|---|---|---|
| Composer | Hebdomadaire (lundi 06:00 Africa/Libreville) | `laravel-ecosystem`, `dev-deps` |
| NPM | Hebdomadaire | `react-ecosystem`, `radix-ui`, `lint-tools` |
| GitHub Actions | Mensuelle | – |

Dependabot ouvre automatiquement des PR de mise à jour, groupées par catégorie pour réduire le bruit. Limite 5 PR ouvertes / écosystème.

---

## 3. Templates GitHub

### 3.1 Pull Request — `.github/pull_request_template.md`
Sections : Objet · Type · Périmètre RBM · Détails · Constats audit couverts · Conformité référentielle · **Checklist validation locale** · Tests · Captures · Breaking changes · Plan de bascule.

### 3.2 Issues
- `bug_report.yml` — formulaire structuré (contexte, repro, sévérité, domaine fonctionnel)
- `feature_request.yml` — besoin métier, priorité P0–P3, référentiel mobilisé

### 3.3 CODEOWNERS
Routage des reviewers par domaine :
- Backend → `dsi-backend`
- Frontend → `dsi-frontend`
- Reporting → `dsi-reporting`
- Sécurité (middlewares, auth, RBAC) → `rssi` (revue obligatoire RSSI)
- CI/CD → `dsi-devops`
- Documentation → `dsi`

À ajuster avec les équipes GitHub réelles de la DSI CEEAC.

---

## 4. Branch protection recommandée

À configurer dans **Settings → Branches → Add rule** pour `main` :

- ✅ Require pull request before merging
- ✅ Require approvals (≥ 1, ≥ 2 pour fichiers `rssi`)
- ✅ Dismiss stale pull request approvals when new commits are pushed
- ✅ Require status checks to pass before merging :
  - `Backend (PHP 8.3)`
  - `Frontend (Node 22)`
- ✅ Require branches to be up to date before merging
- ✅ Require conversation resolution before merging
- ✅ Require signed commits (recommandé)
- ✅ Include administrators (P0)
- ❌ Allow force pushes (jamais sur `main`)
- ❌ Allow deletions (jamais sur `main`)

---

## 5. Scripts utiles

### Local (avant push)

```bash
composer quality   # = pint:check + stan + test (toute la chaîne PHP)
npm run typecheck && npm run build  # vérifications front
```

### En CI (équivalent strict)

Les commandes lancées en CI sont identiques à celles disponibles localement — pas de divergence environnement dev/CI.

---

## 6. Trajectoire de durcissement

| Étape | Effort | Statut |
|---|---|---|
| Pipeline initial (Pint + PHPStan + PHPUnit + build) | – | ✅ Livré v1.0 |
| ESLint en strict (retirer continue-on-error) | S | Quand les `any` React seront typés |
| Prettier format check en strict | S | Une fois `prettier --write` exécuté |
| typecheck en strict | S | Déjà à 0 erreur — retirer continue-on-error |
| Couverture de code minimale (ex. 60 %) | M | Ajouter `--coverage` à PHPUnit + seuil |
| Job de déploiement staging | M | Quand un environnement staging est provisionné |
| Job E2E (Playwright) | L | Quand les tests E2E seront écrits |
| Scan de secrets (gitleaks / trufflehog) | S | Ajouter en pre-commit + CI |
| SAST applicatif (Snyk / Semgrep) | M | Phase 2 sécurité |
| Signing artefacts + SBOM | L | Phase compliance |

---

## 7. Conformité référentielle

| Référentiel | Exigence | Couverture |
|---|---|---|
| **COBIT 2019 DSS06** | Gérer les contrôles des processus business | ✅ |
| **ITIL 4 — Service validation** | Tester avant déploiement | ✅ |
| **ISO/IEC 27001 A.14** | Sécurité dans les processus de dev | 🟡 partiel (SAST à venir) |
| **OWASP SAMM** | Verification stream — Security Testing | 🟡 partiel |
| **DevSecOps** | Shift-left security (audit deps + audit code) | ✅ |

---

## 8. Activation côté CEEAC

Étapes pour activer la CI sur le serveur GitHub :

1. **Initialiser le dépôt distant** :
   ```bash
   git init
   git add .
   git commit -m "Init: TB-PAPA-CEEAC v1.0 + CI"
   gh repo create ceeac-eccas/tb-papa --private --source=. --push
   ```

2. **Créer les équipes GitHub** (Organisation Settings → Teams) :
   - `dsi` (équipe DSI complète)
   - `dsi-backend` · `dsi-frontend` · `dsi-reporting` · `dsi-devops`
   - `rssi` (Responsable Sécurité Système d'Information)

3. **Configurer la branch protection** sur `main` (cf. §4).

4. **Activer Dependabot** : Settings → Security → Dependabot alerts + security updates.

5. **Configurer les secrets** (Settings → Secrets and variables → Actions) :
   - `APP_KEY_STAGING` (pour future job de déploiement)
   - `DB_PASSWORD_STAGING`
   - `SENTRY_DSN`
   - Etc.

6. **Premier push** : la pipeline se déclenche, vérifier l'onglet **Actions**.

---

**Fin du document** — version 1.0
