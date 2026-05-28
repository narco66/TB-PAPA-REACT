<?php

namespace App\Console\Commands;

use App\Models\Departement;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Audit complet de conformité de l'architecture organisationnelle TB-PAPA-CEEAC
 * vs structure officielle de la Commission de la CEEAC.
 */
class AuditOrganisationCommand extends Command
{
    protected $signature = 'ceeac:audit-organisation {--json : Sortie JSON}';

    protected $description = 'Audite la conformité de l\'architecture organisationnelle vs la structure officielle CEEAC.';

    /**
     * Structure officielle attendue (codes département + nombre minimum directions/services).
     */
    protected array $structureCible = [
        'PRES' => ['libelle' => 'Présidence de la Commission', 'min_directions' => 6, 'min_services' => 13],
        'VP' => ['libelle' => 'Vice-Présidence', 'min_directions' => 1, 'min_services' => 2],
        'SG' => ['libelle' => 'Secrétariat Général', 'min_directions' => 5, 'min_services' => 14],
        'DAPPS' => ['libelle' => 'Affaires Politiques, Paix et Sécurité', 'min_directions' => 3, 'min_services' => 8],
        'DMCAEMF' => ['libelle' => 'Marché Commun, Affaires Économiques', 'min_directions' => 3, 'min_services' => 5],
        'DERNADER' => ['libelle' => 'Environnement, Ressources Naturelles, Agriculture', 'min_directions' => 2, 'min_services' => 4],
        'DATI' => ['libelle' => 'Aménagement du Territoire et Infrastructures', 'min_directions' => 2, 'min_services' => 4],
        'DPGDHS' => ['libelle' => 'Développement Humain et Social', 'min_directions' => 2, 'min_services' => 4],
    ];

    public function handle(): int
    {
        $rapport = [];

        // === 1. Conformité structurelle ===
        $departementsCeeac = [];
        $manquants = [];
        foreach ($this->structureCible as $code => $cible) {
            $dep = Departement::where('code', $code)->first();
            if (! $dep) {
                $manquants[] = $code;

                continue;
            }
            $nbDir = Direction::where('departement_id', $dep->id)->count();
            $nbSvc = Service::where('departement_id', $dep->id)->count();
            $departementsCeeac[$code] = [
                'libelle' => $dep->libelle,
                'directions' => $nbDir,
                'services' => $nbSvc,
                'cible_directions' => $cible['min_directions'],
                'cible_services' => $cible['min_services'],
                'conforme_directions' => $nbDir >= $cible['min_directions'],
                'conforme_services' => $nbSvc >= $cible['min_services'],
            ];
        }
        $rapport['structure'] = $departementsCeeac;
        $rapport['departements_manquants'] = $manquants;

        // === 2. Utilisateurs orphelins ===
        $rapport['utilisateurs'] = [
            'total' => User::count(),
            'actifs' => User::where('actif', true)->count(),
            'sans_direction' => User::whereNull('direction_id')->count(),
            'sans_service' => User::whereNull('service_id')->count(),
            'sans_role' => User::doesntHave('roles')->count(),
        ];

        // === 3. Couverture des rôles institutionnels ===
        $rolesCritiques = ['president', 'vice_president', 'secretaire_general', 'commissaire',
            'directeur_technique', 'directeur_appui', 'chef_service',
            'audit_interne', 'controle_financier'];
        $rolesCouverture = [];
        foreach ($rolesCritiques as $r) {
            $role = \Spatie\Permission\Models\Role::where('name', $r)->first();
            $count = $role ? $role->users()->count() : 0;
            $rolesCouverture[$r] = [
                'count' => $count,
                'conforme' => $count > 0,
            ];
        }
        $rapport['roles'] = $rolesCouverture;

        // === 4. Directions / Services sans responsable ===
        $rapport['lacunes_responsables'] = [
            'departements_sans_commissaire' => Departement::whereNull('commissaire_id')->where('code', '!=', 'PRES')->where('code', '!=', 'VP')->where('code', '!=', 'SG')->pluck('code')->all(),
            'directions_sans_directeur' => Direction::whereNull('directeur_id')->pluck('code')->all(),
            'services_sans_chef' => Service::whereNull('chef_service_id')->pluck('code')->all(),
        ];

        // === 5. Score de conformité ===
        $score = 0;
        $max = 0;
        foreach ($departementsCeeac as $d) {
            $score += $d['conforme_directions'] ? 1 : 0;
            $score += $d['conforme_services'] ? 1 : 0;
            $max += 2;
        }
        foreach ($rolesCouverture as $r) {
            $score += $r['conforme'] ? 1 : 0;
            $max += 1;
        }
        $rapport['score_conformite'] = $max > 0 ? round($score / $max * 100, 1) : 0;

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->afficherRapport($rapport);

        return self::SUCCESS;
    }

    protected function afficherRapport(array $r): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' AUDIT DE CONFORMITÉ ORGANISATIONNELLE — TB-PAPA-CEEAC');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $this->line('<fg=yellow>📊 SCORE DE CONFORMITÉ GLOBAL :</> <fg=green;options=bold>' . $r['score_conformite'] . '%</>');
        $this->newLine();

        // Structure
        $this->info('▶ Structure départementale');
        $rows = [];
        foreach ($r['structure'] as $code => $d) {
            $rows[] = [
                $code,
                $d['libelle'],
                $d['directions'] . '/' . $d['cible_directions'] . ($d['conforme_directions'] ? ' ✓' : ' ✗'),
                $d['services'] . '/' . $d['cible_services'] . ($d['conforme_services'] ? ' ✓' : ' ✗'),
            ];
        }
        $this->table(['Code', 'Libellé', 'Directions', 'Services'], $rows);

        if (! empty($r['departements_manquants'])) {
            $this->error('⚠️  Départements manquants : ' . implode(', ', $r['departements_manquants']));
        } else {
            $this->info('✓ Tous les départements officiels sont présents.');
        }
        $this->newLine();

        // Utilisateurs
        $this->info('▶ Couverture utilisateurs');
        $u = $r['utilisateurs'];
        $this->line("  Total : <fg=cyan>{$u['total']}</> (actifs : <fg=green>{$u['actifs']}</>)");
        if ($u['sans_direction'] > 0) {
            $this->warn("  ⚠️  {$u['sans_direction']} utilisateur(s) sans direction");
        }
        if ($u['sans_service'] > 0) {
            $this->warn("  ⚠️  {$u['sans_service']} utilisateur(s) sans service");
        }
        if ($u['sans_role'] > 0) {
            $this->error("  ✗ {$u['sans_role']} utilisateur(s) sans rôle");
        }
        $this->newLine();

        // Rôles
        $this->info('▶ Couverture des rôles institutionnels');
        foreach ($r['roles'] as $name => $data) {
            $status = $data['conforme'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line(sprintf('  %s %-24s : %d utilisateur(s)', $status, $name, $data['count']));
        }
        $this->newLine();

        // Lacunes responsables
        $l = $r['lacunes_responsables'];
        if (! empty($l['departements_sans_commissaire']) || ! empty($l['directions_sans_directeur']) || ! empty($l['services_sans_chef'])) {
            $this->info('▶ Lacunes responsables');
            if (! empty($l['departements_sans_commissaire'])) {
                $this->warn('  ⚠️  Départements sans commissaire : ' . implode(', ', $l['departements_sans_commissaire']));
            }
            $nbDirSansDirecteur = count($l['directions_sans_directeur']);
            if ($nbDirSansDirecteur > 0) {
                $this->warn("  ⚠️  {$nbDirSansDirecteur} direction(s) sans directeur : " . implode(', ', array_slice($l['directions_sans_directeur'], 0, 10)) . ($nbDirSansDirecteur > 10 ? '...' : ''));
            }
            $nbSvcSansChef = count($l['services_sans_chef']);
            if ($nbSvcSansChef > 0) {
                $this->warn("  ⚠️  {$nbSvcSansChef} service(s) sans chef de service");
            }
            $this->newLine();
        }

        $this->info('═══════════════════════════════════════════════════════');
        $this->line(' Pour appliquer les corrections : <fg=cyan>php artisan ceeac:apply-corrections</>');
        $this->info('═══════════════════════════════════════════════════════');
    }
}
