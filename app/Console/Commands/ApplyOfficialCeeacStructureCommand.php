<?php

namespace App\Console\Commands;

use App\Models\Departement;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Applique strictement la structure organisationnelle officielle CEEAC.
 *
 * Refonte ciblée :
 *   - Renomme DAEM, DPES (ajout de "des" pour conformité libellé)
 *   - Ajoute 3 services manquants sous DMC
 *   - Refonte complète DERNADER (3 directions + 9 services)
 *   - Refonte complète DATI (3 directions + 7 services)
 *   - Refonte complète DPGDHS (4 directions + 8 services)
 *
 * Préservation :
 *   - Soft-delete des anciennes directions/services (rollback possible)
 *   - Détachement utilisateurs (direction_id → null si direction supprimée, conservation département)
 *   - Activity log Spatie sur chaque action
 */
class ApplyOfficialCeeacStructureCommand extends Command
{
    protected $signature = 'ceeac:apply-official-structure {--dry-run}';

    protected $description = 'Applique strictement la structure organisationnelle officielle CEEAC.';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' REFONTE — STRUCTURE OFFICIELLE CEEAC');
        $this->info('═══════════════════════════════════════════════════════');
        if ($dryRun) {
            $this->warn(' MODE DRY-RUN — aucune modification');
        }
        $this->newLine();

        $journal = [
            'renommages' => 0,
            'services_crees' => 0,
            'directions_creees' => 0,
            'directions_supprimees' => 0,
            'services_supprimes' => 0,
            'utilisateurs_detaches' => 0,
        ];

        DB::transaction(function () use ($dryRun, &$journal) {
            $this->renommagesLeger($dryRun, $journal);
            $this->ajoutServicesDMC($dryRun, $journal);
            $this->refonteDERNADER($dryRun, $journal);
            $this->refonteDATI($dryRun, $journal);
            $this->refonteDPGDHS($dryRun, $journal);
        });

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' JOURNAL DE REFONTE');
        $this->info('═══════════════════════════════════════════════════════');
        foreach ($journal as $cle => $val) {
            $this->line("  ▶ " . str_pad(str_replace('_', ' ', $cle), 30) . ": <fg=green>{$val}</>");
        }
        $this->newLine();
        if ($dryRun) {
            $this->warn(' Aucune modification appliquée (dry-run).');
        } else {
            $this->info(' ✓ Refonte terminée. Lancez ceeac:audit-organisation pour vérifier.');
        }

        return self::SUCCESS;
    }

    protected function renommagesLeger(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Renommages mineurs (DAEM, DPES)');
        $renames = [
            'DAEM' => 'Direction des Affaires Économiques et Monétaires',
            'DPES' => 'Direction des Prévisions Économiques et des Statistiques',
        ];
        foreach ($renames as $code => $libelle) {
            $dir = Direction::where('code', $code)->first();
            if ($dir && $dir->libelle !== $libelle) {
                $this->line("  ↻ {$code} → {$libelle}");
                if (! $dryRun) {
                    $dir->update(['libelle' => $libelle]);
                }
                $journal['renommages']++;
            }
        }
    }

    protected function ajoutServicesDMC(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Services DMC (Marché Commun)');
        $dmc = Direction::where('code', 'DMC')->first();
        if (! $dmc) {
            return;
        }

        $services = [
            ['DMC-S01', 'Service Affaires Douanières et Facilitation des Échanges'],
            ['DMC-S02', 'Service Politique Commerciale, Concurrence et Promotion des Investissements'],
            ['DMC-S03', 'Service Libre Circulation et Droits d\'Établissement'],
        ];

        foreach ($services as [$code, $libelle]) {
            $exists = Service::where('code', $code)->exists();
            if ($exists) {
                continue;
            }
            $this->line("  + {$code} → {$libelle}");
            if (! $dryRun) {
                Service::create([
                    'code' => $code, 'libelle' => $libelle,
                    'direction_id' => $dmc->id, 'departement_id' => $dmc->departement_id,
                    'actif' => true, 'statut' => 'actif',
                ]);
            }
            $journal['services_crees']++;
        }
    }

    protected function refonteDERNADER(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Refonte DERNADER (Environnement, Ressources Naturelles, Agriculture, Dév. Rural)');
        $dep = Departement::where('code', 'DERNADER')->first();
        if (! $dep) {
            return;
        }

        // Supprimer anciennes directions
        $this->supprimerDirections($dep, ['DENVC', 'DAGR', 'DFDR'], $dryRun, $journal);

        // Créer nouvelle structure conforme
        $structures = [
            ['DERN', 'Direction Environnement et Ressources Naturelles', 'technique', [
                ['DERN-S01', 'Service Gestion des Ressources Naturelles'],
                ['DERN-S02', 'Service Environnement et Biodiversité'],
                ['DERN-S03', 'Service Gestion des Risques et Catastrophes'],
            ]],
            ['DADR', 'Direction Agriculture et Développement Rural', 'technique', [
                ['DADR-S01', 'Service Agriculture, Alimentation et Nutrition'],
                ['DADR-S02', 'Service Élevage et Pêche'],
                ['DADR-S03', 'Service Développement Rural'],
            ]],
            ['CRCGRE', 'Centre Régional de Coordination et Gestion des Ressources en Eau', 'technique', [
                ['CRCGRE-S01', 'Service Gestion du Système d\'Information sur l\'Eau'],
                ['CRCGRE-S02', 'Service Politiques, Recherche et Développement'],
            ]],
        ];

        $this->creerStructure($dep, $structures, $dryRun, $journal);
    }

    protected function refonteDATI(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Refonte DATI (Aménagement du Territoire et Infrastructures)');
        $dep = Departement::where('code', 'DATI')->first();
        if (! $dep) {
            return;
        }

        $this->supprimerDirections($dep, ['DTRANS', 'DENER', 'DTIC'], $dryRun, $journal);

        $structures = [
            ['DATT', 'Direction Aménagement du Territoire et Transports', 'technique', [
                ['DATT-S01', 'Service Aménagement du Territoire'],
                ['DATT-S02', 'Service Transport Routier, Ferroviaire et Fluvial'],
                ['DATT-S03', 'Service Transport Aérien et Maritime'],
            ]],
            ['DPTEN', 'Direction Postes, Télécommunications et Économie Numérique', 'technique', [
                ['DPTEN-S01', 'Service Postes et Télécommunications'],
                ['DPTEN-S02', 'Service Économie Numérique'],
            ]],
            ['DENERGIE', 'Direction de l\'Énergie', 'technique', [
                ['DENERGIE-S01', 'Service Règlementation et Statistiques'],
                ['DENERGIE-S02', 'Service Énergies Nouvelles et Renouvelables'],
            ]],
        ];

        $this->creerStructure($dep, $structures, $dryRun, $journal);
    }

    protected function refonteDPGDHS(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Refonte DPGDHS (Promotion du Genre, Développement Humain et Social)');
        $dep = Departement::where('code', 'DPGDHS')->first();
        if (! $dep) {
            return;
        }

        $this->supprimerDirections($dep, ['DGSPS', 'DSE', 'DJECS'], $dryRun, $journal);

        $structures = [
            ['DGPF', 'Direction Genre et Promotion de la Femme', 'technique', [
                ['DGPF-S01', 'Service Genre et Renforcement des Capacités'],
                ['DGPF-S02', 'Service Promotion de la Femme'],
            ]],
            ['DSAS', 'Direction Santé et Affaires Sociales', 'technique', [
                ['DSAS-S01', 'Service Santé'],
                ['DSAS-S02', 'Service Affaires Sociales'],
            ]],
            ['DJSE', 'Direction Jeunesse, Sports et Emploi', 'technique', [
                ['DJSE-S01', 'Service Jeunesse et Sports'],
                ['DJSE-S02', 'Service Emploi'],
            ]],
            ['DECDT', 'Direction Éducation, Culture et Développement Technologique', 'technique', [
                ['DECDT-S01', 'Service Éducation et Développement Technologique'],
                ['DECDT-S02', 'Service Culture'],
            ]],
        ];

        $this->creerStructure($dep, $structures, $dryRun, $journal);
    }

    /** Soft-delete des directions listées + détache les utilisateurs (direction_id, service_id → null). */
    protected function supprimerDirections(Departement $dep, array $codes, bool $dryRun, array &$journal): void
    {
        foreach ($codes as $code) {
            $dir = Direction::where('code', $code)->where('departement_id', $dep->id)->first();
            if (! $dir) {
                continue;
            }

            // Détacher utilisateurs : on conserve le département (cohérent), on enlève direction et service
            $nbUsers = User::where('direction_id', $dir->id)->count();
            if ($nbUsers > 0) {
                $this->line("  ⚠ {$nbUsers} utilisateur(s) détaché(s) de {$code}");
                if (! $dryRun) {
                    User::where('direction_id', $dir->id)->update(['direction_id' => null, 'service_id' => null]);
                }
                $journal['utilisateurs_detaches'] += $nbUsers;
            }

            // Soft-delete des services
            $services = Service::where('direction_id', $dir->id)->get();
            foreach ($services as $svc) {
                if (! $dryRun) {
                    $svc->delete();
                }
                $journal['services_supprimes']++;
            }

            $this->line("  − Direction supprimée : [{$code}] {$dir->libelle}");
            if (! $dryRun) {
                $dir->delete();
            }
            $journal['directions_supprimees']++;
        }
    }

    /** Crée la nouvelle structure (directions + services). */
    protected function creerStructure(Departement $dep, array $structures, bool $dryRun, array &$journal): void
    {
        foreach ($structures as [$code, $libelle, $type, $services]) {
            $exists = Direction::where('code', $code)->exists();
            if ($exists) {
                continue;
            }
            $this->line("  + Direction : [{$code}] {$libelle}");
            if (! $dryRun) {
                $dir = Direction::create([
                    'code' => $code, 'libelle' => $libelle,
                    'type' => $type, 'departement_id' => $dep->id, 'actif' => true,
                ]);
                foreach ($services as [$svcCode, $svcLibelle]) {
                    Service::create([
                        'code' => $svcCode, 'libelle' => $svcLibelle,
                        'direction_id' => $dir->id, 'departement_id' => $dep->id,
                        'actif' => true, 'statut' => 'actif',
                    ]);
                    $journal['services_crees']++;
                }
            } else {
                $journal['services_crees'] += count($services);
            }
            $journal['directions_creees']++;
        }
    }
}
