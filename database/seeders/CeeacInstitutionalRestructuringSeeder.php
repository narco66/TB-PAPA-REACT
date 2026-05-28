<?php

namespace Database\Seeders;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Papa;
use App\Models\Service;
use App\Models\User;
use App\Services\Budget\BudgetNomenclatureService;
use App\Services\Rbm\RecalculAvancementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CeeacInstitutionalRestructuringSeeder extends Seeder
{
    private array $users = [];

    private array $departements = [];

    private array $directions = [];

    private array $services = [];

    private array $sources = [];

    private array $partners = [];

    private ?int $adminId = null;

    public function __construct(private readonly BudgetNomenclatureService $nomenclature) {}

    public function run(?int $year = 2026): void
    {
        $year ??= 2026;

        $this->backupApplicationTables();
        $this->purgeApplicationData();

        DB::transaction(function () use ($year) {
            $this->call(RolesPermissionsSeeder::class);
            $this->seedUsersAndInstitution();
            $this->seedFunding();
            $papa = $this->seedRbm($year);
            $exercices = $this->seedBudget($year);
            $this->seedExpenseChain($year, $exercices[$year]);
            $this->seedInstitutionalRecords($year, $papa);
            $this->seedAudit($year);
            $this->seedReportsImportsAndNotifications($year);
            $this->recalculate($papa, $exercices);
            $this->writeCoherenceReport($year);
        });
    }

    private function backupApplicationTables(): void
    {
        $dir = storage_path('app/backups/ceeac-restructuring/' . now()->format('Ymd-His'));
        File::ensureDirectoryExists($dir);

        foreach ($this->targetTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            File::put($dir . DIRECTORY_SEPARATOR . $table . '.json', DB::table($table)->get()->toJson(JSON_PRETTY_PRINT));
        }

        File::put($dir . DIRECTORY_SEPARATOR . 'README.txt', "Backup automatique avant restructuration CEEAC genere le " . now()->toDateTimeString());
    }

    private function purgeApplicationData(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($this->targetTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $driver === 'sqlite'
                ? DB::table($table)->delete()
                : DB::table($table)->truncate();
        }

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function targetTables(): array
    {
        return [
            'alerte_destinataires', 'alertes', 'notifications',
            'audit_suivis_recommandations', 'audit_recommandations', 'audit_constats', 'audit_mission_equipe', 'audit_missions', 'audit_plans',
            'liquidation_details', 'receptions', 'service_done_certificates', 'commitment_lines', 'expense_requests',
            'budget_import_erreurs', 'budget_import_mappings', 'budget_imports',
            'budget_mouvements', 'mouvements_budgetaires', 'budgets', 'budget_lignes', 'budget_paragraphes', 'budget_articles', 'budget_chapitres',
            'budget_sources_financement', 'budget_exercices',
            'documents', 'generated_reports', 'validations',
            'valeurs_indicateurs', 'indicateurs', 'taches', 'activite_dependances', 'activites', 'sous_produits', 'produits', 'axes', 'papas',
            'organizational_units', 'services', 'directions', 'departements',
            'model_has_roles', 'model_has_permissions', 'sessions', 'users',
            'suppliers', 'partenaires',
        ];
    }

    private function seedUsersAndInstitution(): void
    {
        $this->adminId = $this->user('Administrateur DSI CEEAC', 'admin@ceeac.org', 'Administrateur technique', 'SYS-0001', 'admin_technique');
        $president = $this->user('Mahamat Saleh Annadif', 'president@ceeac.int', 'President de la Commission', 'PRES-0001', 'president');
        $vp = $this->user('Awa Fatime Mahamat', 'vice-president@ceeac.int', 'Vice-President de la Commission', 'VP-0001', 'vice_president');
        $sg = $this->user('Andre Mbata Mangu', 'sg@ceeac.int', 'Secretaire General', 'SG-0001', 'secretaire_general');

        $root = $this->unit('CEEAC', 'Commission de la CEEAC', 'commission', null, $president, 1);
        $presUnit = $this->unit('PRES', 'Presidence de la Commission', 'presidence', $root, $president, 1);
        $vpUnit = $this->unit('VP', 'Vice-Presidence', 'vice_presidence', $root, $vp, 2);
        $sgUnit = $this->unit('SG', 'Secretariat General', 'secretariat_general', $root, $sg, 3);

        $structure = $this->officialStructure();

        foreach ($structure as $dIndex => $department) {
            $commissaireId = $department['role'] === 'commissaire'
                ? $this->user($department['head'], $this->email($department['head'], 'commissaire'), 'Commissaire - ' . $department['label'], 'COM-' . str_pad((string) ($dIndex + 1), 4, '0', STR_PAD_LEFT), 'commissaire')
                : match ($department['code']) {
                    'PRES' => $president,
                    'VP' => $vp,
                    'SG' => $sg,
                    default => null,
                };

            $dep = Departement::create([
                'code' => $department['code'],
                'libelle' => $department['label'],
                'description' => 'Structure officielle de la Commission de la CEEAC.',
                'commissaire_id' => $commissaireId,
                'ordre' => $dIndex + 1,
                'actif' => true,
            ]);
            $this->departements[$department['code']] = $dep;

            $parentUnit = match ($department['code']) {
                'PRES' => $presUnit,
                'VP' => $vpUnit,
                'SG' => $sgUnit,
                default => $root,
            };
            $depUnit = $this->unit('OU-' . $department['code'], $department['label'], 'departement', $parentUnit, $commissaireId, $dIndex + 1, $dep->id);

            foreach ($department['directions'] as $dirIndex => $direction) {
                $role = $department['code'] === 'SG' || in_array($department['code'], ['PRES', 'VP'], true) ? 'directeur_appui' : 'directeur_technique';
                $directorId = $this->user($direction['head'], $this->email($direction['head'], 'directeur'), 'Directeur - ' . $direction['label'], 'DIR-' . $direction['code'], $role);
                $dir = Direction::create([
                    'code' => $direction['code'],
                    'libelle' => $direction['label'],
                    'type' => $role === 'directeur_appui' ? 'appui_soutien' : 'technique',
                    'departement_id' => $dep->id,
                    'directeur_id' => $directorId,
                    'description' => 'Direction alignee sur la nouvelle architecture institutionnelle.',
                    'actif' => true,
                ]);
                $this->directions[$direction['code']] = $dir;
                User::whereKey($directorId)->update(['direction_id' => $dir->id]);
                $dirUnit = $this->unit($direction['code'], $direction['label'], 'direction', $depUnit, $directorId, $dirIndex + 1, $dep->id, $dir->id);

                foreach ($direction['services'] as $srvIndex => $serviceLabel) {
                    $chefId = $this->user($this->serviceHeadName($serviceLabel, $srvIndex), $this->email($serviceLabel, 'service'), 'Chef de service - ' . $serviceLabel, 'SRV-' . $direction['code'] . '-' . str_pad((string) ($srvIndex + 1), 2, '0', STR_PAD_LEFT), 'chef_service', $dir->id);
                    $service = Service::create([
                        'code' => $direction['code'] . '-S' . str_pad((string) ($srvIndex + 1), 2, '0', STR_PAD_LEFT),
                        'libelle' => $serviceLabel,
                        'direction_id' => $dir->id,
                        'chef_service_id' => $chefId,
                        'description' => 'Service operationnel officiel.',
                        'ordre' => $srvIndex + 1,
                        'actif' => true,
                    ]);
                    $this->services[$service->code] = $service;
                    $srvUnit = $this->unit($service->code, $serviceLabel, 'service', $dirUnit, $chefId, $srvIndex + 1, $dep->id, $dir->id, $service->id);
                    User::whereKey($chefId)->update(['direction_id' => $dir->id, 'service_id' => $service->id, 'organizational_unit_id' => $srvUnit]);

                    foreach (['Expert', 'Cadre', 'Assistant'] as $profileIndex => $profile) {
                        $name = $this->staffName($profileIndex + $srvIndex);
                        $this->user($name, $this->email($serviceLabel . ' ' . $profile . ' ' . $profileIndex, 'agent'), $profile . ' - ' . $serviceLabel, 'AG-' . strtoupper(Str::random(6)), $profile === 'Assistant' ? 'point_focal' : 'point_focal', $dir->id, $service->id, $srvUnit);
                    }
                }
            }
        }

        User::whereKey($this->adminId)->update(['direction_id' => $this->directions['DSI']?->id ?? null]);
    }

    private function officialStructure(): array
    {
        return [
            ['code' => 'PRES', 'label' => 'Presidence de la Commission', 'role' => 'president', 'head' => 'Mahamat Saleh Annadif', 'directions' => [
                ['code' => 'CABP', 'label' => 'Cabinet du President', 'head' => 'Justine Moundounga', 'services' => ['Directeur de Cabinet', 'Conseillers', 'Service Courrier']],
                ['code' => 'BCJ', 'label' => 'Bureau du Conseiller Juridique', 'head' => 'Aristide Ondo', 'services' => ['Service Conventions, Accords et Documents Solennels', 'Service Affaires Reglementaires et Contentieuses']],
                ['code' => 'ACC', 'label' => 'Agence Comptable Centrale', 'head' => 'Clarisse Moukoko', 'services' => ['Service Comptabilite', 'Service Comptabilite des Projets et Programmes', 'Service Recouvrement et Tresorerie']],
                ['code' => 'AI', 'label' => 'Audit Interne', 'head' => 'Blaise Nguema', 'services' => ['Service Audit Interne', 'Service Audit des Projets et Programmes']],
                ['code' => 'CFC', 'label' => 'Controle Financier Central', 'head' => 'Marthe Mba', 'services' => ['Service Controle Financier', 'Service Controle Financier des Programmes et Projets']],
                ['code' => 'BL', 'label' => 'Bureaux de Liaison', 'head' => 'Hassan Abakar', 'services' => ['Experts', 'Personnel d appui']],
            ]],
            ['code' => 'VP', 'label' => 'Vice-Presidence', 'role' => 'vice_president', 'head' => 'Awa Fatime Mahamat', 'directions' => [
                ['code' => 'CABVP', 'label' => 'Cabinet du Vice-President', 'head' => 'Patrick Moukouri', 'services' => ['Chef de Cabinet', 'Charges d Etudes']],
            ]],
            ['code' => 'SG', 'label' => 'Secretariat General', 'role' => 'sg', 'head' => 'Andre Mbata Mangu', 'directions' => [
                ['code' => 'DCRPP', 'label' => 'Direction Communication, Relations Publiques et Protocole', 'head' => 'Nadia Bekale', 'services' => ['Service Communication et Relations Publiques', 'Centre de Documentation et Archives', 'Service Protocole et Ceremonial', 'Service Traduction et Interpretariat']],
                ['code' => 'DCMR', 'label' => 'Direction Cooperation et Mobilisation des Ressources', 'head' => 'Eric Wamba', 'services' => ['Service Cooperation', 'Service Mobilisation des Ressources']],
                ['code' => 'DPPB', 'label' => 'Direction Planification, Programmes et Budget', 'head' => 'Grace Ndong', 'services' => ['Service Planification et Suivi-Evaluation', 'Service Programmes et Projets', 'Service Budget']],
                ['code' => 'DRHMG', 'label' => 'Direction Ressources Humaines et Moyens Generaux', 'head' => 'Carine Moundounga', 'services' => ['Service Administration des Ressources Humaines', 'Service Developpement des Ressources Humaines', 'Service Moyens Generaux']],
                ['code' => 'DSI', 'label' => 'Direction des Systemes d Information', 'head' => 'Cedric Etoundi', 'services' => ['Service Etudes et Developpement', 'Service Exploitation et Maintenance']],
            ]],
            ['code' => 'DAPPS', 'label' => 'Departement Affaires Politiques, Paix et Securite', 'role' => 'commissaire', 'head' => 'Fatima Abakar', 'directions' => [
                ['code' => 'DAP', 'label' => 'Direction Affaires Politiques', 'head' => 'Alain Tchicaya', 'services' => ['Service Elections, Gouvernance Democratique et Droits Humains', 'Service Mediation et Diplomatie Preventive']],
                ['code' => 'DMARAC', 'label' => 'Direction MARAC et Securite', 'head' => 'Serge Ondo', 'services' => ['Service Observation et Banque de Donnees', 'Service Evaluation et Analyses', 'Service Securite']],
                ['code' => 'EMR', 'label' => 'Etat-Major Regional', 'head' => 'Jean-Baptiste Kengne', 'services' => ['Composante Militaire', 'Composante Police Gendarmerie', 'Composante Civile', 'Composante Appui et Soutien']],
            ]],
            ['code' => 'DMCAEMF', 'label' => 'Departement Marche Commun, Affaires Economiques, Monetaires et Financieres', 'role' => 'commissaire', 'head' => 'Christian Moukoko', 'directions' => [
                ['code' => 'DAEM', 'label' => 'Direction Affaires Economiques et Monetaires', 'head' => 'Dieudonne Mbemba', 'services' => ['Service Politiques Economiques, Monetaires et Fiscales', 'Service Industrie et Promotion du Secteur Prive']],
                ['code' => 'DPES', 'label' => 'Direction Previsions Economiques et Statistiques', 'head' => 'Aline Nzeyimana', 'services' => ['Service Analyses et Previsions Economiques', 'Service Gestion des Bases de Donnees']],
                ['code' => 'DMC', 'label' => 'Direction du Marche Commun', 'head' => 'Herve Assango', 'services' => ['Service Union Douaniere', 'Service Libre Circulation et Facilitation Commerciale']],
            ]],
            ['code' => 'DERNADER', 'label' => 'Departement Environnement, Ressources Naturelles, Agriculture et Developpement Rural', 'role' => 'commissaire', 'head' => 'Honorine Tabuna', 'directions' => [
                ['code' => 'DENVC', 'label' => 'Direction Environnement, Climat et Biodiversite', 'head' => 'Judith Mavoungou', 'services' => ['Service Environnement et Climat', 'Service Biodiversite et Aires Protegees', 'Service Gestion des Risques et Adaptation']],
                ['code' => 'DAGR', 'label' => 'Direction Agriculture, Elevage et Peche', 'head' => 'Rose Kanyinda', 'services' => ['Service Agriculture et Securite Alimentaire', 'Service Elevage et Sante Animale', 'Service Peche et Aquaculture']],
                ['code' => 'DFDR', 'label' => 'Direction Forets et Developpement Rural', 'head' => 'Priscille Atangana', 'services' => ['Service Forets et Ressources Naturelles', 'Service Developpement Rural et Territoires']],
            ]],
            ['code' => 'DATI', 'label' => 'Departement Amenagement du Territoire et Infrastructures', 'role' => 'commissaire', 'head' => 'Brice Koumba', 'directions' => [
                ['code' => 'DTRANS', 'label' => 'Direction Transport et Corridors', 'head' => 'Patrick Mba', 'services' => ['Service Transport Multimodal', 'Service Corridors et Facilitation']],
                ['code' => 'DENER', 'label' => 'Direction Energie et Integration Physique', 'head' => 'Lucien Biyoghe', 'services' => ['Service Energie et Interconnexions', 'Service Integration Physique Regionale']],
                ['code' => 'DTIC', 'label' => 'Direction TIC et Infrastructures Numeriques', 'head' => 'Yannick Essono', 'services' => ['Service TIC Regionales', 'Service Infrastructures Numeriques']],
            ]],
            ['code' => 'DPGDHS', 'label' => 'Departement Promotion du Genre, Developpement Humain et Social', 'role' => 'commissaire', 'head' => 'Yvette Ngandu', 'directions' => [
                ['code' => 'DGSPS', 'label' => 'Direction Genre et Protection Sociale', 'head' => 'Mireille Nguema', 'services' => ['Service Genre et Autonomisation', 'Service Protection Sociale']],
                ['code' => 'DSE', 'label' => 'Direction Sante et Education', 'head' => 'Awa Idrissa', 'services' => ['Service Sante Communautaire', 'Service Education et Formation']],
                ['code' => 'DJECS', 'label' => 'Direction Jeunesse, Emploi, Culture et Sport', 'head' => 'Claudia Mvondo', 'services' => ['Service Emploi et Jeunesse', 'Service Culture et Sport']],
            ]],
        ];
    }

    private function seedFunding(): void
    {
        foreach ([
            ['CEEAC-EM', 'Contributions statutaires des Etats membres', 'interne', 'etat_membre'],
            ['UE', 'Union Europeenne', 'externe', 'multilateral'],
            ['BAD', 'Banque Africaine de Developpement', 'externe', 'multilateral'],
            ['BM', 'Banque Mondiale', 'externe', 'multilateral'],
            ['PNUD', 'Programme des Nations Unies pour le Developpement', 'externe', 'multilateral'],
            ['GIZ', 'Cooperation allemande GIZ', 'externe', 'bilateral'],
        ] as $i => [$code, $label, $type, $category]) {
            $partnerId = null;
            if ($type === 'externe') {
                $partnerId = DB::table('partenaires')->insertGetId([
                    'code' => $code,
                    'libelle' => $label,
                    'type' => $category,
                    'contact_principal' => 'Point focal ' . $code,
                    'email' => strtolower($code) . '@partners.ceeac.int',
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->partners[$code] = $partnerId;
            }

            $this->sources[$code] = DB::table('budget_sources_financement')->insertGetId([
                'code' => $code,
                'libelle' => $label,
                'type' => $type,
                'categorie' => $category,
                'partenaire_id' => $partnerId,
                'description' => 'Source de financement institutionnelle.',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedRbm(int $year): Papa
    {
        $papa = Papa::create([
            'annee' => $year,
            'version' => 'CEEAC-REFONTE',
            'libelle' => "Plan d Actions Prioritaires Annuel {$year}",
            'description' => 'PAPA restructure selon la nouvelle architecture officielle de la Commission de la CEEAC.',
            'perimetre_institutionnel' => 'Presidence, Vice-Presidence, Secretariat General et cinq departements techniques.',
            'statut' => 'valide',
            'date_debut' => "{$year}-01-01",
            'date_fin' => "{$year}-12-31",
            'date_validation' => now()->subDays(15),
            'valide_par_id' => $this->users['president@ceeac.int'] ?? null,
            'created_by' => $this->adminId,
        ]);

        $axes = [
            ['DAPPS', 'Paix, securite et gouvernance democratique'],
            ['DMCAEMF', 'Marche commun, convergence economique et integration commerciale'],
            ['DERNADER', 'Environnement, climat, agriculture et developpement rural durable'],
            ['DATI', 'Amenagement du territoire, infrastructures et connectivite regionale'],
            ['DPGDHS', 'Genre, developpement humain, sante, education et inclusion sociale'],
            ['SG', 'Performance institutionnelle, budget, digitalisation et redevabilite'],
        ];

        foreach ($axes as $a => [$depCode, $label]) {
            $dep = $this->departements[$depCode];
            $axeId = DB::table('axes')->insertGetId([
                'papa_id' => $papa->id,
                'code' => 'AXE-' . ($a + 1),
                'libelle' => $label,
                'description' => 'Axe strategique alimente par les departements et directions responsables.',
                'statut' => 'valide',
                'ordre' => $a + 1,
                'poids' => 100 / count($axes),
                'taux_execution' => 0,
                'date_debut' => "{$year}-01-15",
                'date_fin' => "{$year}-12-15",
                'departement_id' => $dep->id,
                'responsable_id' => $dep->commissaire_id,
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $departmentDirections = Direction::where('departement_id', $dep->id)->take(3)->get();
            foreach ($departmentDirections as $p => $direction) {
                $produitId = DB::table('produits')->insertGetId([
                    'axe_id' => $axeId,
                    'code' => 'P.' . ($a + 1) . '.' . ($p + 1),
                    'libelle' => 'Produit ' . ($p + 1) . ' - Capacites et mecanismes regionaux renforces',
                    'description' => 'Produit RBM lie a ' . $direction->libelle,
                    'statut' => 'valide',
                    'ordre' => $p + 1,
                    'poids' => 33.33,
                    'taux_execution' => 0,
                    'date_debut' => "{$year}-02-01",
                    'date_fin' => "{$year}-11-30",
                    'direction_id' => $direction->id,
                    'responsable_id' => $direction->directeur_id,
                    'created_by' => $this->adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach (Service::where('direction_id', $direction->id)->take(2)->get() as $s => $service) {
                    $spId = DB::table('sous_produits')->insertGetId([
                        'produit_id' => $produitId,
                        'code' => 'SP.' . ($a + 1) . '.' . ($p + 1) . '.' . ($s + 1),
                        'libelle' => 'Sous-produit ' . ($s + 1) . ' - ' . $service->libelle,
                        'description' => 'Livrable institutionnel mesurable.',
                        'statut' => 'valide',
                        'ordre' => $s + 1,
                        'poids' => 50,
                        'taux_execution' => 0,
                        'date_debut' => "{$year}-02-15",
                        'date_fin' => "{$year}-11-15",
                        'direction_id' => $direction->id,
                        'responsable_id' => $service->chef_service_id,
                        'created_by' => $this->adminId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->seedIndicators($spId, $service->chef_service_id, $year);
                    $this->seedActivities($spId, $direction->id, $service->chef_service_id, $year, $a, $p, $s);
                }
            }
        }

        return $papa;
    }

    private function seedIndicators(int $sousProduitId, ?int $responsableId, int $year): void
    {
        foreach (['Taux de mise en oeuvre', 'Nombre de livrables valides'] as $i => $label) {
            $target = $i === 0 ? 100 : 12;
            $indicatorId = DB::table('indicateurs')->insertGetId([
                'sous_produit_id' => $sousProduitId,
                'code' => 'KPI-' . $sousProduitId . '-' . ($i + 1),
                'libelle' => $label,
                'definition' => 'Indicateur de performance institutionnelle.',
                'type' => 'quantitatif',
                'unite' => $i === 0 ? '%' : 'nombre',
                'baseline' => 0,
                'cible' => $target,
                'date_baseline' => "{$year}-01-01",
                'methode_calcul' => 'Valeur observee / cible annuelle.',
                'frequence_collecte' => 'trimestrielle',
                'source_donnees' => 'Rapports trimestriels et pieces GED.',
                'responsable_id' => $responsableId,
                'valeur_actuelle' => 0,
                'taux_realisation' => 0,
                'tendance' => 'stable',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            for ($q = 1; $q <= 4; $q++) {
                $value = $i === 0 ? min(100, 18 * $q + ($sousProduitId % 7)) : min($target, 2 * $q + ($sousProduitId % 3));
                DB::table('valeurs_indicateurs')->insert([
                    'indicateur_id' => $indicatorId,
                    'date_observation' => Carbon::create($year, $q * 3, 25)->toDateString(),
                    'periode_libelle' => 'T' . $q . ' ' . $year,
                    'valeur' => $value,
                    'commentaire' => 'Mesure trimestrielle consolidee.',
                    'source_verification' => 'Rapport trimestriel valide',
                    'saisi_par_id' => $responsableId,
                    'valide_at' => now()->subDays(5),
                    'valide_par_id' => $this->adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('indicateurs')->where('id', $indicatorId)->update([
                'valeur_actuelle' => $value,
                'taux_realisation' => round(($value / $target) * 100, 2),
                'tendance' => 'hausse',
            ]);
        }
    }

    private function seedActivities(int $spId, int $directionId, ?int $responsableId, int $year, int $a, int $p, int $s): void
    {
        foreach (['Planifier et cadrer', 'Executer les livrables', 'Consolider et rapporter'] as $act => $label) {
            $activityId = DB::table('activites')->insertGetId([
                'sous_produit_id' => $spId,
                'code' => 'ACT.' . ($a + 1) . '.' . ($p + 1) . '.' . ($s + 1) . '.' . ($act + 1),
                'libelle' => $label,
                'description' => 'Activite RBM avec calendrier, responsable et niveau de risque.',
                'statut' => ['planifiee', 'en_cours', 'realisee'][$act],
                'ordre' => $act + 1,
                'poids' => 33.33,
                'taux_execution' => [15, 55, 100][$act],
                'date_debut' => Carbon::create($year, 2 + $act * 3, 1)->toDateString(),
                'date_fin' => Carbon::create($year, 4 + $act * 3, 20)->toDateString(),
                'date_debut_reelle' => $act > 0 ? Carbon::create($year, 2 + $act * 3, 3)->toDateString() : null,
                'date_fin_reelle' => $act === 2 ? Carbon::create($year, 10, 25)->toDateString() : null,
                'niveau_risque' => ['faible', 'moyen', 'eleve'][($a + $act) % 3],
                'direction_id' => $directionId,
                'responsable_id' => $responsableId,
                'point_focal_id' => $responsableId,
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['Termes de reference', 'Mobilisation des parties prenantes', 'Archivage des preuves'] as $t => $taskLabel) {
                DB::table('taches')->insert([
                    'activite_id' => $activityId,
                    'code' => 'T.' . $activityId . '.' . ($t + 1),
                    'libelle' => $taskLabel,
                    'description' => 'Tache operationnelle suivie dans la chaine de resultats.',
                    'statut' => ['planifiee', 'en_cours', 'realisee'][$t],
                    'ordre' => $t + 1,
                    'poids' => 33.33,
                    'taux_execution' => [10, 60, 100][$t],
                    'date_debut' => Carbon::create($year, 2 + $act * 3, 5 + $t)->toDateString(),
                    'date_fin' => Carbon::create($year, 3 + $act * 3, 15 + $t)->toDateString(),
                    'responsable_id' => $responsableId,
                    'assigne_a_id' => $responsableId,
                    'created_by' => $this->adminId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedBudget(int $year): array
    {
        $exercices = [];
        foreach (range(2025, 2030) as $annee) {
            $exercices[$annee] = BudgetExercice::create([
                'annee' => $annee,
                'libelle' => "Budget institutionnel CEEAC {$annee}",
                'description' => 'Budget multi-sources conserve selon le modele TOTAL = CEEAC + PTF.',
                'statut' => $annee < $year ? 'cloture' : ($annee === $year ? 'valide' : 'soumis'),
                'date_debut' => "{$annee}-01-01",
                'date_fin' => "{$annee}-12-31",
                'devise' => 'XAF',
                'valide_par_id' => $this->users['president@ceeac.int'] ?? null,
                'valide_at' => $annee <= $year ? now()->subDays(10) : null,
                'created_by' => $this->adminId,
            ]);
        }

        $activities = DB::table('activites')->get();
        $tasks = DB::table('taches')->get();
        $budgetLabels = ['Missions regionales', 'Ateliers techniques', 'Assistance technique', 'Equipements informatiques', 'Etudes et enquetes', 'Communication institutionnelle', 'Maintenance plateformes', 'Appui aux projets regionaux'];

        foreach ($exercices as $annee => $exercice) {
            foreach (range(1, 48) as $i) {
                $activity = $activities[($i - 1) % max(1, $activities->count())] ?? null;
                $task = $tasks[($i - 1) % max(1, $tasks->count())] ?? null;
                $directionId = $activity?->direction_id;
                $direction = $directionId ? Direction::find($directionId) : null;
                $total = (120 + ($i * 17) + ($annee - 2025) * 20) * 1_000_000;
                $ptf = $i % 3 === 0 ? (int) round($total * 0.38) : 0;
                $ceeac = $total - $ptf;
                $sourceCode = $ptf > 0 ? ['UE', 'BAD', 'BM', 'PNUD', 'GIZ'][$i % 5] : 'CEEAC-EM';
                $code = (string) (60100 + $i + (($annee - 2025) * 100));
                $data = $this->nomenclature->normaliserLigne([
                    'exercice_id' => $exercice->id,
                    'budget_ligne_code' => $code,
                    'code_action' => $code,
                    'libelle' => $budgetLabels[$i % count($budgetLabels)],
                    'description' => 'Ligne budgetaire rattachee a la chaine RBM/GAR.',
                    'nature' => 'depense',
                    'type_budget' => ['fonctionnement', 'investissement', 'equipement', 'transfert'][$i % 4],
                    'pilier' => ($i % 6) + 1,
                    'montant_total' => $total,
                    'montant_ceeac_em' => $ceeac,
                    'montant_ptf' => $ptf,
                    'budget_annee_precedente' => max(0, $total - 25_000_000),
                    'realisation_annee_precedente' => max(0, $total - 45_000_000),
                    'taux_realisation_precedent' => 72 + ($i % 20),
                    'variation' => -5 + ($i % 18),
                    'source_financement_id' => $this->sources[$sourceCode],
                    'partenaire_id' => $sourceCode === 'CEEAC-EM' ? null : ($this->partners[$sourceCode] ?? null),
                    'activite_id' => $activity?->id,
                    'tache_id' => $task?->id,
                    'departement_id' => $direction?->departement_id,
                    'direction_id' => $directionId,
                    'statut' => $annee <= $year ? 'valide' : 'soumis',
                    'ordre' => $i,
                    'observations' => 'Restructuration CEEAC - imputation coherente.',
                    'created_by' => $this->adminId,
                    'updated_by' => $this->adminId,
                    'validated_by' => $this->adminId,
                    'validated_at' => now(),
                ], $exercice, $this->adminId);

                $ligne = BudgetLigne::create($data);
                if ($annee === $year && $i <= 24) {
                    $this->seedBudgetMovements($ligne, $i);
                }
            }
        }

        return $exercices;
    }

    private function seedBudgetMovements(BudgetLigne $ligne, int $i): void
    {
        $engage = round($ligne->montant_total * (0.25 + (($i % 5) * 0.08)), 2);
        $liquide = round($engage * 0.72, 2);
        $ordonnance = round($liquide * 0.92, 2);
        $paye = round($ordonnance * 0.86, 2);
        $parentId = null;

        foreach ([['engagement', $engage], ['liquidation', $liquide], ['ordonnancement', $ordonnance], ['paiement', $paye]] as $step => [$type, $amount]) {
            $movementId = DB::table('budget_mouvements')->insertGetId([
                'ligne_id' => $ligne->id,
                'type' => $type,
                'type_engagement' => $step === 0 ? ['achat_biens', 'prestation_services', 'mission_officielle', 'formation_atelier'][$i % 4] : null,
                'montant' => $amount,
                'date_mouvement' => now()->subDays(80 - $i - $step)->toDateString(),
                'reference' => strtoupper($type) . '-CEEAC-' . str_pad((string) $ligne->id, 5, '0'),
                'motif' => 'Cycle budgetaire institutionnel: ' . $type,
                'saisi_par_id' => $this->adminId,
                'valide_at' => now()->subDays(75 - $i - $step),
                'valide_par_id' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $parentId = $movementId ?: $parentId;
        }

        $ligne->update([
            'montant_engage' => $engage,
            'montant_liquide' => $liquide,
            'montant_ordonnance' => $ordonnance,
            'montant_paye' => $paye,
            'montant_disponible' => max(0, $ligne->montant_total - $engage),
            'taux_consommation' => round(($paye / max(1, $ligne->montant_total)) * 100, 2),
        ]);
    }

    private function seedExpenseChain(int $year, BudgetExercice $exercice): void
    {
        foreach (['Koumba Consulting SARL', 'Sahel Data Services', 'Equator Logistics', 'Central Africa Training Group', 'Bureau Regional Ingenierie'] as $i => $name) {
            DB::table('suppliers')->insert([
                'code' => 'FOUR-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'libelle' => $name,
                'type' => 'personne_morale',
                'nif' => 'NIF' . rand(100000, 999999),
                'rccm' => 'RCCM-' . strtoupper(Str::random(8)),
                'contact_principal' => $this->staffName($i),
                'email' => 'contact' . ($i + 1) . '@supplier.example',
                'telephone' => '+241 11 ' . rand(100000, 999999),
                'adresse' => 'Libreville, Gabon',
                'pays' => 'Gabon',
                'compte_bancaire' => 'GA' . rand(100000000, 999999999),
                'banque' => 'Banque regionale',
                'statut' => 'actif',
                'observations' => 'Fournisseur de demonstration institutionnelle.',
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $lines = DB::table('budget_lignes')->where('exercice_id', $exercice->id)->limit(12)->get();
        $suppliers = DB::table('suppliers')->get();
        foreach ($lines as $i => $line) {
            $supplier = $suppliers[$i % $suppliers->count()];
            DB::table('expense_requests')->insert([
                'numero' => 'EB-' . $year . '-' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'exercice_id' => $exercice->id,
                'demandeur_id' => $this->adminId,
                'departement_id' => $line->departement_id,
                'direction_id' => $line->direction_id,
                'activite_id' => $line->activite_id,
                'tache_id' => $line->tache_id,
                'type_engagement' => ['achat_biens', 'prestation_services', 'mission_officielle', 'formation_atelier'][$i % 4],
                'objet' => 'Expression de besoin - ' . $line->libelle,
                'justification' => 'Besoin lie a la mise en oeuvre du PAPA.',
                'description_detaillee' => 'Demande documentee avec imputation budgetaire et pieces justificatives.',
                'montant_estime' => min($line->montant_total, 55_000_000 + ($i * 4_000_000)),
                'montant_estime_ceeac' => min($line->montant_ceeac_em, 35_000_000 + ($i * 2_000_000)),
                'montant_estime_ptf' => min($line->montant_ptf, 20_000_000 + ($i * 2_000_000)),
                'devise' => 'XAF',
                'source_financement_id' => $line->source_financement_id,
                'supplier_pressenti_id' => $supplier->id,
                'date_besoin_prevu' => now()->addDays(15 + $i)->toDateString(),
                'date_livraison_souhaitee' => now()->addDays(45 + $i)->toDateString(),
                'statut' => ['soumis', 'valide', 'engage'][$i % 3],
                'valideur_hierarchique_id' => $this->adminId,
                'valide_at' => now()->subDays(3),
                'motif_decision' => 'Visa hierarchique favorable.',
                'priorite' => ($i % 4) + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedInstitutionalRecords(int $year, Papa $papa): void
    {
        foreach (DB::table('activites')->limit(20)->get() as $i => $activity) {
            DB::table('documents')->insert([
                'documentable_type' => 'App\\Models\\Activite',
                'documentable_id' => $activity->id,
                'categorie' => ['execution', 'validation', 'financier', 'suivi_evaluation'][$i % 4],
                'libelle' => ['Compte rendu de reunion', 'Decision institutionnelle', 'Note de mission', 'Memorandum technique'][$i % 4],
                'description' => 'Piece GED rattachee a la restructuration institutionnelle.',
                'nom_fichier' => 'CEEAC-GED-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT) . '.pdf',
                'chemin_stockage' => 'private/ged/ceeac-demo-' . ($i + 1) . '.pdf',
                'mime_type' => 'application/pdf',
                'taille_octets' => 180000 + ($i * 1200),
                'hash_sha256' => hash('sha256', 'ged-' . $year . '-' . $i),
                'uploade_par_id' => $this->adminId,
                'confidentiel' => $i % 5 === 0,
                'valide_at' => now()->subDays(2),
                'valide_par_id' => $this->adminId,
                'version' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('validations')->insert([
                'validable_type' => 'App\\Models\\Activite',
                'validable_id' => $activity->id,
                'etape' => ['soumission', 'revue_technique', 'validation_directeur', 'validation_sg'][$i % 4],
                'decision' => ['approuve', 'en_attente', 'renvoye'][$i % 3],
                'demandeur_id' => $this->adminId,
                'valideur_id' => $this->adminId,
                'decide_at' => $i % 3 === 1 ? null : now()->subDays($i),
                'commentaire' => 'Validation coherente avec le circuit institutionnel.',
                'donnees_avant' => json_encode(['statut' => 'soumis']),
                'donnees_apres' => json_encode(['statut' => $activity->statut]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('axes')->limit(8)->get() as $i => $axe) {
            DB::table('alertes')->insert([
                'alertable_type' => 'App\\Models\\Axe',
                'alertable_id' => $axe->id,
                'niveau' => ['info', 'attention', 'critique'][$i % 3],
                'categorie' => ['retard', 'derive_budgetaire', 'sous_performance', 'risque'][$i % 4],
                'titre' => 'Alerte de pilotage ' . ($i + 1),
                'message' => 'Alerte automatique issue des controles de coherence RBM, budget et delais.',
                'contexte' => json_encode(['papa_id' => $papa->id, 'axe_id' => $axe->id]),
                'automatique' => true,
                'statut' => ['ouverte', 'en_traitement', 'resolue'][$i % 3],
                'assignee_a_id' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAudit(int $year): void
    {
        $planId = DB::table('audit_plans')->insertGetId([
            'annee' => $year,
            'libelle' => "Plan annuel d audit interne {$year}",
            'description' => 'Couverture des risques institutionnels, budgetaires, RBM/GAR et SI.',
            'orientation_strategique' => 'Gouvernance, conformite budgetaire, qualite des donnees et efficacite des controles.',
            'statut' => 'valide',
            'date_debut' => "{$year}-01-15",
            'date_fin' => "{$year}-12-15",
            'valide_par_id' => $this->adminId,
            'valide_at' => now()->subDays(20),
            'created_by' => $this->adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['Audit RBM/GAR', 'Audit du cycle budgetaire', 'Audit de la GED', 'Audit des acces et permissions'] as $i => $title) {
            $dep = Departement::skip($i)->first();
            $dir = Direction::where('departement_id', $dep?->id)->first();
            $missionId = DB::table('audit_missions')->insertGetId([
                'plan_id' => $planId,
                'code' => 'AUD-' . $year . '-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'titre' => $title,
                'objectifs' => 'Evaluer la maturite du controle interne et la fiabilite des donnees.',
                'perimetre' => 'Unites organisationnelles, workflows, budget, RBM et reporting.',
                'type' => ['performance', 'financier', 'conformite', 'systeme_information'][$i],
                'priorite' => ['haute', 'critique', 'moyenne', 'haute'][$i],
                'statut' => ['planifiee', 'en_cours', 'projet_rapport', 'cloturee'][$i],
                'date_debut_prevue' => Carbon::create($year, $i + 2, 1)->toDateString(),
                'date_fin_prevue' => Carbon::create($year, $i + 3, 25)->toDateString(),
                'chef_mission_id' => $this->adminId,
                'departement_audite_id' => $dep?->id,
                'direction_auditee_id' => $dir?->id,
                'lettre_mission' => 'Lettre de mission validee et archivee GED.',
                'synthese' => 'Synthese executive provisoire.',
                'created_by' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('audit_mission_equipe')->insert(['mission_id' => $missionId, 'user_id' => $this->adminId, 'role_mission' => 'chef', 'created_at' => now(), 'updated_at' => now()]);
            $constatId = DB::table('audit_constats')->insertGetId([
                'mission_id' => $missionId,
                'code' => 'C-' . ($i + 1),
                'libelle' => 'Controle de coherence a renforcer',
                'description' => 'Les controles existent et doivent etre systematises dans les workflows.',
                'preuves' => 'Journaux applicatifs, rapports, extractions budgetaires.',
                'gravite' => ['majeur', 'critique', 'moyen', 'mineur'][$i],
                'nature' => ['controle_insuffisant', 'risque', 'non_conformite', 'inefficience'][$i],
                'cause_racine' => 'Heterogeneite des sources et traitements manuels residuels.',
                'impact' => 'Risque de retard de consolidation et de retraitement.',
                'saisi_par_id' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $recId = DB::table('audit_recommandations')->insertGetId([
                'constat_id' => $constatId,
                'code' => 'R-' . ($i + 1),
                'libelle' => 'Automatiser le controle et le reporting',
                'action_proposee' => 'Formaliser un controle automatise et produire un rapport periodique.',
                'priorite' => ['haute', 'urgente', 'moyenne', 'basse'][$i],
                'responsable_mise_en_oeuvre_id' => $this->adminId,
                'date_echeance' => now()->addMonths($i + 2)->toDateString(),
                'statut' => ['ouverte', 'en_cours', 'mise_en_oeuvre', 'verifiee'][$i],
                'pourcentage_avancement' => [0, 45, 70, 100][$i],
                'saisi_par_id' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('audit_suivis_recommandations')->insert([
                'recommandation_id' => $recId,
                'date_suivi' => now()->subDays(7)->toDateString(),
                'etat_avancement' => ['non_demarre', 'en_cours', 'en_cours', 'realise'][$i],
                'pourcentage' => [0, 45, 70, 100][$i],
                'actions_realisees' => 'Actions de mise en conformite engagees.',
                'actions_restantes' => 'Validation finale et archivage.',
                'commentaire' => 'Suivi periodique de recommandation.',
                'suivi_par_id' => $this->adminId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedReportsImportsAndNotifications(int $year): void
    {
        foreach (['dashboard_executif', 'matrice_rbm', 'budget_consolide', 'matrice_risques', 'journal_audit', 'export_kpi'] as $i => $key) {
            DB::table('generated_reports')->insert([
                'report_key' => $key,
                'categorie' => ['analytique', 'performance', 'budget', 'audit'][$i % 4],
                'titre' => Str::headline($key),
                'description' => 'Rapport genere par la restructuration CEEAC.',
                'filtres' => json_encode(['annee' => $year, 'architecture' => 'CEEAC-REFONTE']),
                'chemin_stockage' => 'private/reports/ceeac/' . $key . '-' . $year . '.' . ($i % 3 === 0 ? 'xlsx' : 'pdf'),
                'nom_fichier' => strtoupper($key) . '-' . $year . '.' . ($i % 3 === 0 ? 'xlsx' : 'pdf'),
                'taille_octets' => 240000 + ($i * 30000),
                'hash_sha256' => hash('sha256', $key . $year),
                'format' => $i % 3 === 0 ? 'xlsx' : 'pdf',
                'nb_pages' => 6 + $i,
                'code_verification' => 'CEEAC-' . $year . '-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'signe_numeriquement' => true,
                'signature_hash' => hash('sha256', 'signature-' . $key),
                'genere_par_id' => $this->adminId,
                'genere_at' => now()->subDays($i),
                'nb_telechargements' => $i,
                'archive_ged' => false,
                'statut' => 'pret',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $exerciceId = DB::table('budget_exercices')->where('annee', $year)->value('id');
        foreach (range(1, 4) as $i) {
            $importId = DB::table('budget_imports')->insertGetId([
                'exercice_id' => $exerciceId,
                'fichier_nom' => 'CEEAC-template-budget-' . $i . '.xlsx',
                'chemin_stockage' => 'budget-imports/ceeac-template-' . $i . '.xlsx',
                'taille_octets' => 125000 + ($i * 3000),
                'hash_sha256' => hash('sha256', 'import-' . $i),
                'feuille_source' => ['Budget', 'PAPA', 'Contributions', 'Imputations'][$i - 1],
                'type_donnees' => 'budget',
                'statut' => ['reussi', 'reussi', 'a_corriger', 'prevu'][$i - 1],
                'nb_lignes_lues' => 120 + ($i * 15),
                'nb_lignes_creees' => 40 + ($i * 7),
                'nb_lignes_mises_a_jour' => $i * 2,
                'nb_erreurs' => $i === 3 ? 2 : 0,
                'journal' => json_encode(['architecture' => 'CEEAC-REFONTE']),
                'options' => json_encode(['controle_total_ceeac_ptf' => true]),
                'execute_par_id' => $this->adminId,
                'execute_at' => now()->subDays($i),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($i === 3) {
                DB::table('budget_import_erreurs')->insert([
                    'import_id' => $importId,
                    'feuille' => 'Contributions',
                    'ligne' => 12,
                    'colonne' => 'Montant PTF',
                    'valeur_fautive' => '#N/A',
                    'gravite' => 'erreur',
                    'regle' => 'format_invalide',
                    'message' => 'Montant non numerique detecte.',
                    'correction_suggeree' => 'Renseigner une valeur numerique.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        DB::table('budget_import_mappings')->insert([
            'user_id' => $this->adminId,
            'libelle' => 'Mapping CEEAC Budget Multi-Sources',
            'type_donnees' => 'budget',
            'mapping' => json_encode(['Code' => 'budget_ligne_code', 'Total' => 'montant_total', 'CEEAC' => 'montant_ceeac_em', 'PTF' => 'montant_ptf']),
            'partage' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (User::limit(30)->get() as $user) {
            DB::table('notifications')->insert([
                'id' => (string) Str::uuid(),
                'type' => 'CeeacInstitutionalNotification',
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['titre' => 'Validation requise', 'message' => 'Une action institutionnelle attend votre revue.']),
                'read_at' => $user->id % 3 === 0 ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function recalculate(Papa $papa, array $exercices): void
    {
        app(RecalculAvancementService::class)->recalculerPapa($papa->id);
        foreach ($exercices as $exercice) {
            $exercice->recalculerTotaux();
        }
    }

    private function writeCoherenceReport(int $year): void
    {
        $checks = [
            'departements' => DB::table('departements')->count(),
            'directions' => DB::table('directions')->count(),
            'services' => Schema::hasTable('services') ? DB::table('services')->count() : 0,
            'users' => DB::table('users')->count(),
            'axes' => DB::table('axes')->count(),
            'produits' => DB::table('produits')->count(),
            'sous_produits' => DB::table('sous_produits')->count(),
            'activites' => DB::table('activites')->count(),
            'taches' => DB::table('taches')->count(),
            'budget_lignes' => DB::table('budget_lignes')->count(),
            'budget_mouvements' => DB::table('budget_mouvements')->count(),
            'documents' => DB::table('documents')->count(),
            'generated_reports' => DB::table('generated_reports')->count(),
            'orphan_directions' => DB::table('directions')->whereNotNull('departement_id')->whereNotExists(fn ($q) => $q->selectRaw(1)->from('departements')->whereColumn('departements.id', 'directions.departement_id'))->count(),
            'budget_incoherent' => DB::table('budget_lignes')->whereRaw('ABS((montant_ceeac_em + montant_ptf) - montant_total) > 0.01')->count(),
        ];

        File::ensureDirectoryExists(storage_path('app/reports/restructuring'));
        File::put(storage_path("app/reports/restructuring/ceeac-restructuring-{$year}.json"), json_encode($checks, JSON_PRETTY_PRINT));
    }

    private function user(string $name, string $email, string $function, string $matricule, string $role, ?int $directionId = null, ?int $serviceId = null, ?int $unitId = null): int
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'matricule' => $matricule,
            'fonction' => $function,
            'telephone' => '+241 ' . rand(600000000, 699999999),
            'bio' => 'Cadre institutionnel de la Commission de la CEEAC, rattache a la nouvelle architecture organisationnelle.',
            'profile_photo_path' => 'profiles/' . Str::slug($name) . '.jpg',
            'direction_id' => $directionId,
            'service_id' => $serviceId,
            'organizational_unit_id' => $unitId,
            'actif' => true,
            'email_verified_at' => now(),
            'password' => Hash::make('Password@2026'),
        ]);

        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user->assignRole($role);
        $this->users[$email] = $user->id;

        return $user->id;
    }

    private function unit(string $code, string $label, string $type, ?int $parentId, ?int $responsableId, int $order, ?int $depId = null, ?int $dirId = null, ?int $serviceId = null): int
    {
        $data = [
            'code' => $code,
            'libelle' => $label,
            'type' => $type,
            'parent_id' => $parentId,
            'departement_id' => $depId,
            'direction_id' => $dirId,
            'service_id' => $serviceId,
            'responsable_id' => $responsableId,
            'description' => 'Unite de l organigramme officiel CEEAC.',
            'ordre' => $order,
            'actif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('organizational_units', 'uuid')) {
            $data['uuid'] = (string) Str::uuid();
        }

        if (Schema::hasColumn('organizational_units', 'niveau')) {
            $data['niveau'] = $parentId
                ? ((int) DB::table('organizational_units')->where('id', $parentId)->value('niveau') + 1)
                : 0;
        }

        return DB::table('organizational_units')->insertGetId($data);
    }

    private function email(string $source, string $prefix): string
    {
        $hash = substr(md5($prefix . '|' . $source), 0, 6);

        return Str::slug($prefix . '.' . Str::limit($source, 34, '') . '.' . $hash, '.') . '@ceeac.int';
    }

    private function serviceHeadName(string $serviceLabel, int $index): string
    {
        return $this->staffName($index) . ' ' . Str::of($serviceLabel)->explode(' ')->first();
    }

    private function staffName(int $index): string
    {
        $names = [
            'Arielle Moutsinga', 'Nadia Bekale', 'Serge Ondo', 'Mireille Nguema', 'Jean-Baptiste Kengne',
            'Dieudonne Mbemba', 'Grace Ndong', 'Hassan Mahamat', 'Priscille Atangana', 'Alain Tchicaya',
            'Irma Manirakiza', 'Patrick Mba', 'Rose Kanyinda', 'Brice Koumba', 'Awa Idrissa',
            'Yannick Essono', 'Claudia Mvondo', 'Gael Ngoma', 'Carine Moundounga', 'Lucien Biyoghe',
            'Aline Nzeyimana', 'Cedric Etoundi', 'Olga Dackam', 'Nestor Moukouri', 'Judith Mavoungou',
        ];

        return $names[$index % count($names)];
    }
}
