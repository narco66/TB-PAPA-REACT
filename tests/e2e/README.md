# Tests E2E — TB-PAPA-CEEAC

Tests end-to-end browser-based avec **Playwright**, en complément des 194+ tests PHPUnit Feature.

## Architecture

| Niveau | Outil | Couverture |
| --- | --- | --- |
| **E2E browser** | Playwright | Parcours UI réels (navigation, formulaires, downloads) |
| **E2E HTTP** | PHPUnit Feature (`tests/Feature/Expense/ExpenseE2EScenariosTest.php`) | Workflows complets côté serveur (12 scénarios) |
| **Unitaires** | PHPUnit (autres `tests/Feature/...`) | Services, models, controllers |

## Installation (une seule fois)

```bash
# Installer Playwright + binaire Chromium
npm install --save-dev @playwright/test
npm run test:e2e:install
```

> **Note Windows** : si l'installation du binaire échoue, exécuter manuellement
> `npx playwright install chromium` en tant qu'administrateur.

## Lancement

```bash
# 1. Lancer le serveur Laravel
php artisan serve

# 2. (Optionnel) Recharger les données de démo
php artisan tbpapa:seed-demo --append

# 3. Dans un autre terminal, lancer les tests E2E
npm run test:e2e               # Mode headless (CI-friendly)
npm run test:e2e:ui            # Mode UI interactif (debug)
npm run test:e2e:debug         # Mode headed avec inspector
```

## Configuration

Fichier : `playwright.config.ts`

| Option | Valeur par défaut | Comment changer |
| --- | --- | --- |
| `baseURL` | `http://127.0.0.1:8000` | Variable env `E2E_BASE_URL` |
| `locale` | `fr-FR` | Modifier dans `use:` |
| `timezone` | `Africa/Libreville` | Modifier dans `use:` |
| `workers` | 1 (sérial) | Augmenter une fois la suite isolée |
| `retries` | 2 en CI, 0 en local | Modifier `retries:` |

## Tests existants

### `expense-chain.spec.ts` — Chaîne de la dépense (8 tests)

| Test | Vérifie |
| --- | --- |
| Page d'accueil publique | Modules visibles, lien connexion |
| Login admin | Redirection dashboard |
| Tableau de bord Chaîne dépense | KPI affichés |
| Navigation Expressions du besoin | Page index |
| Création d'une expression | Formulaire + redirection détail |
| Génération PDF rapide | Endpoint `/rapports/{key}/quick` |
| Export Excel | Download `.xlsx` |
| Sidebar | Items « Chaîne de la dépense » visibles |

## Compte de test par défaut

| Email | Password | Rôle |
| --- | --- | --- |
| `admin@ceeac.org` | `Password@2026` | admin_technique (toutes permissions) |

Voir `database/seeders/InstitutionSeeder.php` pour la liste complète des comptes.

## Bonnes pratiques

✅ **À faire**
- Utiliser des sélecteurs accessibles (`getByRole`, `getByLabel`) plutôt que des CSS classes fragiles
- Isoler les tests : chaque `test()` doit pouvoir s'exécuter indépendamment
- Préférer `page.waitForURL` à `page.waitForTimeout`
- Tester les parcours métier critiques (pas chaque bouton)

❌ **À éviter**
- Sélecteurs CSS instables (`.css-1abc`)
- Tests qui dépendent d'un état partagé non explicite
- Assertions floues (`toBeVisible` sans contexte)

## Étendre les tests

Pour ajouter un nouveau scénario :

```ts
test('Description du scénario métier', async ({ page }) => {
    await login(page, 'directeur.dpsih@ceeac.org', 'Password@2026');
    await page.goto('/expense/requests');
    // ... assertions ...
});
```

Pour tester un autre rôle, utiliser un email institutionnel différent (voir `InstitutionSeeder`).

## CI/CD

```yaml
# Exemple GitHub Actions
- name: Run E2E tests
  run: |
    php artisan serve --port=8000 &
    sleep 5
    npm run test:e2e:install
    npm run test:e2e
```

## Diagnostic

- **Test échoue avec timeout** : vérifier que `php artisan serve` est actif
- **Login échoue** : vérifier la BD seedée (`php artisan db:seed`)
- **Sélecteurs introuvables** : utiliser `npx playwright codegen http://127.0.0.1:8000` pour générer du code

## Documentation officielle

- https://playwright.dev/
- https://playwright.dev/docs/locators
- https://playwright.dev/docs/best-practices

---

**Tests E2E maintenus par la DSI CEEAC** · Module Chaîne de la dépense · Phase 8.
