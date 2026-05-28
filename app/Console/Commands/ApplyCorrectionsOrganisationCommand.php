<?php

namespace App\Console\Commands;

use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Corrige les écarts d'organisation détectés par ceeac:audit-organisation.
 *
 * Actions non destructives :
 *   - Crée les utilisateurs manquants pour rôles audit_interne et controle_financier
 *   - Réaffecte les utilisateurs orphelins vers la direction par défaut
 *   - Désigne automatiquement chefs de service / directeurs manquants
 *   - Préserve toutes les données existantes
 */
class ApplyCorrectionsOrganisationCommand extends Command
{
    protected $signature = 'ceeac:apply-corrections {--dry-run : Simule sans modifier}';

    protected $description = 'Applique les corrections d\'organisation détectées par l\'audit (non destructif).';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' CORRECTIONS ORGANISATIONNELLES — TB-PAPA-CEEAC');
        $this->info('═══════════════════════════════════════════════════════');

        if ($dryRun) {
            $this->warn(' MODE DRY-RUN — aucune modification ne sera effectuée');
        }
        $this->newLine();

        $journal = [
            'auditeurs_crees' => 0,
            'controleurs_crees' => 0,
            'orphelins_reaffectes' => 0,
            'chefs_service_designes' => 0,
            'directeurs_designes' => 0,
        ];

        DB::transaction(function () use ($dryRun, &$journal) {
            // === 1. Créer les auditeurs internes ===
            $serviceAudit = Service::where('code', 'AI-S01')->first();
            $serviceAuditProjets = Service::where('code', 'AI-S02')->first();

            $auditeurs = [
                ['Auditeur', 'Senior IGS', 'audit.senior@ceeac.org', $serviceAudit, 'Auditeur Senior IGS'],
                ['Auditeur', 'Junior IGS', 'audit.junior@ceeac.org', $serviceAudit, 'Auditeur Junior IGS'],
                ['Chef', 'Mission Audit Projets', 'audit.projets@ceeac.org', $serviceAuditProjets, 'Chef de mission Audit Projets'],
            ];

            foreach ($auditeurs as [$prenom, $nom, $email, $service, $fonction]) {
                if (! $service) {
                    continue;
                }
                $existe = User::where('email', $email)->exists();
                if ($existe) {
                    continue;
                }
                $this->line("  + Création auditeur : <fg=cyan>{$email}</> → service {$service->code}");
                if (! $dryRun) {
                    $u = User::create([
                        'name' => "{$prenom} {$nom}",
                        'email' => $email,
                        'matricule' => 'AUD-' . str_pad((string) ($journal['auditeurs_crees'] + 1), 3, '0', STR_PAD_LEFT),
                        'fonction' => $fonction,
                        'password' => Hash::make('Password@2026'),
                        'actif' => true,
                        'email_verified_at' => now(),
                        'direction_id' => $service->direction_id,
                        'service_id' => $service->id,
                    ]);
                    $u->assignRole('audit_interne');
                }
                $journal['auditeurs_crees']++;
            }

            // === 2. Créer les contrôleurs financiers ===
            $serviceCfc = Service::where('code', 'CFC-S01')->first();
            $serviceCfcProjets = Service::where('code', 'CFC-S02')->first();

            $controleurs = [
                ['Contrôleur', 'Financier Central', 'cf.central@ceeac.org', $serviceCfc, 'Contrôleur financier central'],
                ['Contrôleur', 'Financier Projets', 'cf.projets@ceeac.org', $serviceCfcProjets, 'Contrôleur financier Programmes/Projets'],
            ];

            foreach ($controleurs as [$prenom, $nom, $email, $service, $fonction]) {
                if (! $service) {
                    continue;
                }
                $existe = User::where('email', $email)->exists();
                if ($existe) {
                    continue;
                }
                $this->line("  + Création contrôleur : <fg=cyan>{$email}</> → service {$service->code}");
                if (! $dryRun) {
                    $u = User::create([
                        'name' => "{$prenom} {$nom}",
                        'email' => $email,
                        'matricule' => 'CF-' . str_pad((string) ($journal['controleurs_crees'] + 1), 3, '0', STR_PAD_LEFT),
                        'fonction' => $fonction,
                        'password' => Hash::make('Password@2026'),
                        'actif' => true,
                        'email_verified_at' => now(),
                        'direction_id' => $service->direction_id,
                        'service_id' => $service->id,
                    ]);
                    $u->assignRole('controle_financier');
                }
                $journal['controleurs_crees']++;
            }

            // === 3. Réaffecter les utilisateurs orphelins (sans direction)
            // On EXCLUT les rôles supérieurs (président, VP, SG, commissaires) qui sont au niveau département ===
            $directionDefault = Direction::where('code', 'DSI')->first();
            $orphelins = User::whereNull('direction_id')
                ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', [
                    'president', 'vice_president', 'secretaire_general', 'commissaire', 'admin_technique',
                ]))
                ->get();

            foreach ($orphelins as $u) {
                $this->line("  → Réaffectation : <fg=cyan>{$u->email}</> → " . ($directionDefault?->libelle ?? 'aucune dir par défaut'));
                if (! $dryRun && $directionDefault) {
                    $u->update(['direction_id' => $directionDefault->id]);
                }
                $journal['orphelins_reaffectes']++;
            }

            // === 4. Désigner directeurs manquants (premier utilisateur du rôle directeur_technique dispo) ===
            $directeursLibres = User::role('directeur_technique')->whereDoesntHave('directionsDirigees')->limit(50)->get();
            $directionsSansDirecteur = Direction::whereNull('directeur_id')->where('type', 'technique')->get();

            foreach ($directionsSansDirecteur as $idx => $dir) {
                $u = $directeursLibres->get($idx);
                if (! $u) {
                    break;
                }
                $this->line("  → Directeur : <fg=cyan>{$u->name}</> → {$dir->code}");
                if (! $dryRun) {
                    $dir->update(['directeur_id' => $u->id]);
                }
                $journal['directeurs_designes']++;
            }

            // === 5. Désigner chefs de service manquants ===
            $chefsLibres = User::role('chef_service')->limit(100)->get();
            $servicesSansChef = Service::whereNull('chef_service_id')->limit(100)->get();

            foreach ($servicesSansChef as $idx => $svc) {
                $u = $chefsLibres->get($idx);
                if (! $u) {
                    break;
                }
                if (! $dryRun) {
                    $svc->update(['chef_service_id' => $u->id]);
                }
                $journal['chefs_service_designes']++;
            }
        });

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' JOURNAL DES CORRECTIONS');
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("  ▶ Auditeurs créés          : <fg=green>{$journal['auditeurs_crees']}</>");
        $this->line("  ▶ Contrôleurs créés        : <fg=green>{$journal['controleurs_crees']}</>");
        $this->line("  ▶ Orphelins réaffectés     : <fg=green>{$journal['orphelins_reaffectes']}</>");
        $this->line("  ▶ Directeurs désignés      : <fg=green>{$journal['directeurs_designes']}</>");
        $this->line("  ▶ Chefs de service désignés: <fg=green>{$journal['chefs_service_designes']}</>");
        $this->newLine();

        if ($dryRun) {
            $this->warn(' Aucune modification appliquée (dry-run).');
        } else {
            $this->info(' ✓ Corrections appliquées. Lancez ceeac:audit-organisation pour vérifier.');
        }

        return self::SUCCESS;
    }
}
