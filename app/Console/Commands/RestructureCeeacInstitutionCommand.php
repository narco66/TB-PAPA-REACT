<?php

namespace App\Console\Commands;

use Database\Seeders\CeeacInstitutionalRestructuringSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RestructureCeeacInstitutionCommand extends Command
{
    protected $signature = 'tbpapa:restructure-ceeac
        {--year=2026 : Annee centrale du PAPA et du budget de demonstration}
        {--migrate : Execute les migrations avant la restructuration}
        {--force : Confirme l execution en environnement de production}';

    protected $description = 'Nettoie les anciennes donnees demo et reconstruit TB-PAPA-CEEAC selon la nouvelle architecture institutionnelle officielle.';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Execution refusee en production sans --force.');

            return self::FAILURE;
        }

        $year = (int) $this->option('year');

        if ($this->option('migrate')) {
            $this->info('Execution des migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        }

        Log::info('tbpapa:restructure-ceeac started', ['year' => $year]);

        $this->warn('Sauvegarde, nettoyage et reconstruction institutionnelle en cours...');
        app(CeeacInstitutionalRestructuringSeeder::class)->run($year);

        $this->info('Restructuration CEEAC terminee.');
        $this->line('Compte administrateur : admin@ceeac.org');
        $this->line('Mot de passe demo : Password@2026');
        $this->line("Rapport de coherence : storage/app/reports/restructuring/ceeac-restructuring-{$year}.json");

        Log::info('tbpapa:restructure-ceeac completed', ['year' => $year]);

        return self::SUCCESS;
    }
}
