<?php

namespace App\Console\Commands;

use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\TbpapaDemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SeedTbpapaDemoCommand extends Command
{
    protected $signature = 'tbpapa:seed-demo
        {--fresh : Réinitialise uniquement les données de démonstration}
        {--append : Ajoute les données sans supprimer l’existant}
        {--year=2026 : Année centrale de démonstration}
        {--departments=all : Périmètre départements}
        {--with-audit : Ajoute les journaux et données d’audit}
        {--with-imports : Ajoute les imports simulés}
        {--with-notifications : Ajoute les notifications}
        {--with-kpi-history : Ajoute l’historique KPI}
        {--rollback : Supprime uniquement les données de démonstration}';

    protected $description = 'Génère un jeu de données institutionnel complet et rollbackable pour TB-PAPA-CEEAC.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->confirm('APP_ENV=production. Confirmez-vous explicitement l’exécution ?')) {
            $this->warn('Opération annulée.');

            return self::FAILURE;
        }

        $year = (int) $this->option('year');
        $fresh = (bool) $this->option('fresh');
        $append = (bool) $this->option('append');
        $rollback = (bool) $this->option('rollback');

        if (! $fresh && ! $append && ! $rollback) {
            $append = true;
        }

        Log::info('tbpapa:seed-demo started', [
            'fresh' => $fresh,
            'append' => $append,
            'rollback' => $rollback,
            'year' => $year,
            'user' => get_current_user(),
        ]);

        if ($rollback || $fresh) {
            $this->warn('Suppression contrôlée des données DEMO uniquement...');
            TbpapaDemoSeeder::rollbackDemo();
            $this->info('Rollback DEMO terminé.');
        }

        if ($rollback) {
            Log::info('tbpapa:seed-demo rollback completed');

            return self::SUCCESS;
        }

        $this->info('Vérification des rôles et permissions...');
        Artisan::call('db:seed', ['--class' => RolesPermissionsSeeder::class, '--force' => true]);

        $this->info("Génération des données DEMO pour {$year}...");
        app(TbpapaDemoSeeder::class)->run($year, [
            'departments' => $this->option('departments'),
            'with_audit' => (bool) $this->option('with-audit'),
            'with_imports' => (bool) $this->option('with-imports'),
            'with_notifications' => (bool) $this->option('with-notifications'),
            'with_kpi_history' => (bool) $this->option('with-kpi-history'),
        ]);

        $this->info('Données DEMO générées avec succès.');
        $this->line('Comptes demo : demo.user01@ceeac.int à demo.user30@ceeac.int');
        $this->line('Mot de passe : Password@2026');

        Log::info('tbpapa:seed-demo completed', ['year' => $year]);

        return self::SUCCESS;
    }
}
