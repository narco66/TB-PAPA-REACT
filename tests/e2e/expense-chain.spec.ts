import { test, expect, type Page } from '@playwright/test';

/**
 * E2E — Chaîne de la dépense TB-PAPA-CEEAC
 *
 * Prérequis :
 *   - Serveur Laravel actif sur http://127.0.0.1:8000
 *   - Compte de démo créé : admin@ceeac.org / Password@2026
 *   - php artisan tbpapa:seed-demo --append
 *
 * Ce fichier sert de squelette pour développer des tests E2E supplémentaires.
 * Étendre par scénario (création expression, validation, engagement…).
 */

const ADMIN_EMAIL = 'admin@ceeac.org';
const ADMIN_PASSWORD = 'Password@2026';

async function login(page: Page, email = ADMIN_EMAIL, password = ADMIN_PASSWORD) {
    await page.goto('/login');
    await page.getByLabel(/email/i).fill(email);
    await page.getByLabel(/mot de passe/i).fill(password);
    await page.getByRole('button', { name: /se connecter/i }).click();
    // 2FA challenge si activé : à compléter selon configuration
    await page.waitForURL(/\/dashboard|\/two-factor-challenge/);
}

test.describe('Chaîne de la dépense — Parcours utilisateur', () => {
    test('Page d\'accueil publique affiche les modules et lien connexion', async ({ page }) => {
        await page.goto('/');
        await expect(page).toHaveTitle(/TB-PAPA/);
        await expect(page.getByRole('link', { name: /connexion/i }).first()).toBeVisible();
        await expect(page.getByText(/Chaîne RBM\/GAR/)).toBeVisible();
    });

    test('Login admin → redirection dashboard', async ({ page }) => {
        await login(page);
        await expect(page).toHaveURL(/\/dashboard/);
    });

    test('Accès au tableau de bord Chaîne de la dépense', async ({ page }) => {
        await login(page);
        await page.goto('/expense');
        await expect(page.getByText(/Pilotage de la chaîne de la dépense/i)).toBeVisible();
        // KPI visibles
        await expect(page.getByText(/Expressions/i).first()).toBeVisible();
        await expect(page.getByText(/Cycle IPSAS/i)).toBeVisible();
    });

    test('Navigation vers Expressions du besoin', async ({ page }) => {
        await login(page);
        await page.goto('/expense/requests');
        await expect(page.getByRole('heading', { name: /Expression du besoin/i }).first()).toBeVisible();
    });

    test('Création d\'une expression du besoin', async ({ page }) => {
        await login(page);
        await page.goto('/expense/requests/create');

        // Remplissage du formulaire
        await page.getByLabel(/Objet/i).fill('Test E2E — Achat fournitures');
        await page.getByLabel(/Justification/i).fill('Test automatisé Playwright pour validation parcours utilisateur');
        await page.getByLabel(/Montant estimé/i).fill('150000');

        await page.getByRole('button', { name: /Créer \(brouillon\)/i }).click();

        // Vérification redirection vers page détail
        await expect(page).toHaveURL(/\/expense\/requests\/\d+/);
        await expect(page.getByText(/Test E2E — Achat fournitures/)).toBeVisible();
        await expect(page.getByText(/brouillon/i)).toBeVisible();
    });

    test('Génération PDF de la fiche d\'expression du besoin', async ({ page, context }) => {
        await login(page);

        // Récupérer une expression existante (depuis l'index)
        await page.goto('/expense/requests');
        const lien = page.locator('a[href^="/expense/requests/"]').first();
        const href = await lien.getAttribute('href');

        if (href) {
            const id = href.split('/').pop();

            // PDF rapide via URL directe
            const pdfPagePromise = context.waitForEvent('page');
            await page.goto(`/rapports/fiche_expression_besoin/quick?expense_request_id=${id}`);
            // Le PDF est servi inline → la page affiche le PDF
            // On vérifie au moins que ça ne renvoie pas une 404
            await expect(page).not.toHaveTitle(/404|Not Found/);
        }
    });

    test('Export Excel du journal des expressions', async ({ page }) => {
        await login(page);
        await page.goto('/expense/requests');

        // Click sur le bouton Exporter
        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('button', { name: /Exporter/i }).first().click();
        await page.getByText(/Excel \(\.xlsx\)/i).click();

        const download = await downloadPromise;
        expect(download.suggestedFilename()).toContain('expressions');
        expect(download.suggestedFilename()).toContain('.xlsx');
    });

    test('Sidebar contient bien la section Chaîne de la dépense', async ({ page }) => {
        await login(page);
        await page.goto('/dashboard');

        await expect(page.getByText(/Chaîne de la dépense/i)).toBeVisible();
        await expect(page.getByRole('link', { name: /Tableau de bord dépense/i })).toBeVisible();
        await expect(page.getByRole('link', { name: /Expressions du besoin/i })).toBeVisible();
        await expect(page.getByRole('link', { name: /Service fait/i })).toBeVisible();
        await expect(page.getByRole('link', { name: /Fournisseurs/i })).toBeVisible();
    });
});
