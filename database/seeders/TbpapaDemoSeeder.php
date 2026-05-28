<?php

namespace Database\Seeders;

use App\Models\Activite;
use App\Models\Alerte;
use App\Models\Audit\AuditConstat;
use App\Models\Audit\AuditMission;
use App\Models\Audit\AuditPlan;
use App\Models\Audit\AuditRecommandation;
use App\Models\Audit\AuditSuiviRecommandation;
use App\Models\Axe;
use App\Models\Budget;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetImportErreur;
use App\Models\Budget\BudgetImportMapping;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\GeneratedReport;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Partenaire;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use App\Models\ValeurIndicateur;
use App\Models\Validation;
use App\Services\Budget\BudgetNomenclatureService;
use App\Services\Rbm\RecalculAvancementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TbpapaDemoSeeder extends Seeder
{
    protected array $users = [];

    protected array $departements = [];

    protected array $directions = [];

    protected array $sources = [];

    public function __construct(protected BudgetNomenclatureService $nomenclature) {}

    public function run(?int $year = 2026, array $options = []): void
    {
        $year ??= 2026;

        DB::transaction(function () use ($year, $options) {
            $this->seedRoles();
            $this->seedUsers();
            $this->seedInstitution();
            $this->seedSourcesAndPartners();
            $papa = $this->seedRbm($year);
            $exercices = $this->seedBudget($year, $papa);
            $this->seedOperationalBudgets($papa);
            $this->seedValidationsAndAlerts($papa);

            if ($options['with_imports'] ?? true) {
                $this->seedImports($exercices[$year] ?? $exercices[array_key_first($exercices)]);
            }
            if ($options['with_notifications'] ?? true) {
                $this->seedNotifications();
            }
            if ($options['with_audit'] ?? true) {
                $this->seedAudit($year);
            }
            if ($options['with_kpi_history'] ?? true) {
                $this->seedGeneratedReports($year);
            }

            app(RecalculAvancementService::class)->recalculerPapa($papa->id);
            foreach ($exercices as $exercice) {
                $exercice->recalculerTotaux();
            }
        });
    }

    public static function rollbackDemo(): void
    {
        DB::transaction(function () {
            DB::table('notifications')->where('type', 'like', 'Demo%')->delete();
            GeneratedReport::where('report_key', 'like', 'demo_%')->forceDelete();
            BudgetImportErreur::whereHas('import', fn ($q) => $q->where('fichier_nom', 'like', 'DEMO-%'))->delete();
            BudgetImport::where('fichier_nom', 'like', 'DEMO-%')->delete();
            BudgetImportMapping::where('libelle', 'like', 'DEMO-%')->delete();

            AuditSuiviRecommandation::whereHas('recommandation.constat.mission', fn ($q) => $q->where('code', 'like', 'DEMO-%'))->delete();
            AuditRecommandation::whereHas('constat.mission', fn ($q) => $q->where('code', 'like', 'DEMO-%'))->forceDelete();
            AuditConstat::whereHas('mission', fn ($q) => $q->where('code', 'like', 'DEMO-%'))->forceDelete();
            AuditMission::where('code', 'like', 'DEMO-%')->forceDelete();
            AuditPlan::where('libelle', 'like', 'DEMO-%')->forceDelete();

            BudgetMouvement::whereHas('ligne', fn ($q) => $q->where('budget_ligne_code', 'like', 'DEMO%')->orWhere('observations', 'like', '%DEMO%'))->delete();
            BudgetLigne::where('budget_ligne_code', 'like', 'DEMO%')->orWhere('observations', 'like', '%DEMO%')->forceDelete();
            Budget::where('observations', 'like', '%DEMO%')->forceDelete();

            Validation::where('commentaire', 'like', '%DEMO%')->delete();
            Alerte::where('titre', 'like', 'DEMO-%')->delete();
            ValeurIndicateur::whereHas('indicateur', fn ($q) => $q->where('code', 'like', 'DEMO-%'))->delete();
            Indicateur::where('code', 'like', 'DEMO-%')->forceDelete();
            Tache::where('code', 'like', 'DEMO-%')->forceDelete();
            Activite::where('code', 'like', 'DEMO-%')->forceDelete();
            SousProduit::where('code', 'like', 'DEMO-%')->forceDelete();
            Produit::where('code', 'like', 'DEMO-%')->forceDelete();
            Axe::where('code', 'like', 'DEMO-%')->forceDelete();
            Papa::where('version', 'DEMO')->forceDelete();
            BudgetExercice::where('libelle', 'like', 'DEMO-%')->forceDelete();

            Direction::where('code', 'like', 'DEMO-%')->forceDelete();
            Departement::where('code', 'like', 'DEMO-%')->forceDelete();
            Partenaire::where('code', 'like', 'DEMO-%')->delete();
            BudgetSourceFinancement::where('code', 'like', 'DEMO-%')->delete();
            User::where('email', 'like', 'demo.%@ceeac.int')->forceDelete();
        });
    }

    protected function seedRoles(): void
    {
        $roles = ['admin_technique', 'president', 'secretaire_general', 'commissaire', 'directeur_technique', 'directeur_appui', 'controle_financier', 'audit_interne', 'point_focal', 'ordonnateur', 'comptable'];
        foreach ($roles as $role) {
            Role::findOrCreate($role);
        }
    }

    protected function seedUsers(): void
    {
        $roles = ['admin_technique', 'president', 'secretaire_general', 'commissaire', 'directeur_technique', 'directeur_appui', 'controle_financier', 'audit_interne', 'point_focal', 'ordonnateur', 'comptable'];
        $names = [
            'Arielle Moutsinga', 'Christian Moukoko', 'Nadia Bekale', 'Serge Ondo', 'Mireille Nguema',
            'Jean-Baptiste Kengne', 'Fatima Abakar', 'Dieudonne Mbemba', 'Grace Ndong', 'Hassan Mahamat',
            'Priscille Atangana', 'Alain Tchicaya', 'Irma Manirakiza', 'Patrick Mba', 'Rose Kanyinda',
            'Brice Koumba', 'Awa Idrissa', 'Yannick Essono', 'Claudia Mvondo', 'Gael Ngoma',
            'Carine Moundounga', 'Lucien Biyoghe', 'Aline Nzeyimana', 'Cedric Etoundi', 'Olga Dackam',
            'Nestor Moukouri', 'Judith Mavoungou', 'Eric Wamba', 'Sandra Kassa', 'Pauline Manyanga',
        ];

        foreach ($names as $i => $name) {
            $user = User::firstOrCreate(
                ['email' => 'demo.user' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '@ceeac.int'],
                [
                    'name' => $name,
                    'matricule' => 'DEMO-U-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'fonction' => $i < 7 ? 'Responsable institutionnel' : 'Point focal PAPA',
                    'password' => Hash::make('Password@2026'),
                    'actif' => true,
                    'email_verified_at' => now(),
                ],
            );
            $user->syncRoles([$roles[$i % count($roles)]]);
            $this->users[] = $user;
        }
    }

    protected function seedInstitution(): void
    {
        $defs = [
            ['PRES', 'Présidence'],
            ['DAPPS', 'Affaires Politiques, Paix et Sécurité'],
            ['DMCAEMF', 'Marché Commun, Affaires Économiques, Monétaires et Financières'],
            ['DERNADER', 'Environnement, Ressources Naturelles, Agriculture et Développement Rural'],
            ['DATI', 'Aménagement du Territoire et Infrastructures'],
            ['DPGDHS', 'Promotion du Genre, Développement Humain et Social'],
            ['SG', 'Secrétariat Général'],
        ];

        foreach ($defs as $i => [$code, $libelle]) {
            $dep = Departement::firstOrCreate(
                ['code' => 'DEMO-' . $code],
                ['libelle' => $libelle, 'description' => 'DEMO - ' . $libelle, 'commissaire_id' => $this->users[$i]->id ?? null, 'ordre' => $i + 1, 'actif' => true],
            );
            $this->departements[] = $dep;
        }

        $serviceLabels = [
            'Cabinet et coordination stratégique', 'Protocole et relations institutionnelles', 'Médiation et diplomatie préventive',
            'Opérations de paix', 'Commerce et intégration régionale', 'Affaires monétaires et financières', 'Statistiques et analyse économique',
            'Agriculture et sécurité alimentaire', 'Environnement et climat', 'Ressources naturelles', 'Infrastructures régionales',
            'Energie et corridors', 'Transport et facilitation', 'Genre et inclusion', 'Santé communautaire', 'Education et jeunesse',
            'Planification et budget', 'Ressources humaines', 'Systèmes d’information', 'Audit et contrôle interne',
        ];
        foreach ($serviceLabels as $i => $label) {
            $dep = $this->departements[$i % count($this->departements)];
            $dir = Direction::firstOrCreate(
                ['code' => 'DEMO-S' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)],
                [
                    'libelle' => $label,
                    'type' => $i >= 16 ? 'appui_soutien' : 'technique',
                    'departement_id' => $dep->id,
                    'directeur_id' => $this->users[($i + 7) % count($this->users)]->id ?? null,
                    'description' => 'DEMO - Service de démonstration pour ' . $label,
                    'actif' => true,
                ],
            );
            $this->directions[] = $dir;
        }
    }

    protected function seedSourcesAndPartners(): void
    {
        $sources = [
            ['DEMO-CEEAC', 'Contributions statutaires des États membres', 'interne'],
            ['DEMO-UE', 'Appui Union Européenne aux programmes régionaux', 'externe'],
            ['DEMO-BAD', 'Financement Banque Africaine de Développement', 'externe'],
            ['DEMO-BM', 'Appui Banque Mondiale aux réformes', 'externe'],
            ['DEMO-PNUD', 'Programme des Nations Unies pour le Développement', 'externe'],
        ];
        foreach ($sources as [$code, $libelle, $type]) {
            $this->sources[] = BudgetSourceFinancement::firstOrCreate(
                ['code' => $code],
                ['libelle' => $libelle, 'type' => $type, 'categorie' => $type === 'interne' ? 'etat_membre' : 'multilateral', 'actif' => true],
            );
        }

        foreach (array_slice($sources, 1) as [$code, $libelle]) {
            Partenaire::firstOrCreate(
                ['code' => $code],
                ['libelle' => $libelle, 'type' => 'multilateral', 'contact_principal' => 'Point focal partenariat', 'email' => strtolower($code) . '@partner.example', 'actif' => true],
            );
        }
    }

    protected function seedRbm(int $year): Papa
    {
        $papa = Papa::firstOrCreate(
            ['annee' => $year, 'version' => 'DEMO'],
            [
                'libelle' => "DEMO - Plan d'Actions Prioritaires Annuel {$year}",
                'description' => 'Jeu de démonstration institutionnel complet pour TB-PAPA-CEEAC.',
                'perimetre_institutionnel' => '7 départements, 20 services, chaîne RBM/GAR complète.',
                'statut' => Papa::STATUT_VALIDE,
                'date_debut' => "{$year}-01-01",
                'date_fin' => "{$year}-12-31",
                'date_validation' => now()->subDays(20),
                'valide_par_id' => $this->users[1]->id ?? null,
                'created_by' => $this->users[2]->id ?? null,
            ],
        );

        $axesLabels = [
            'Paix, sécurité et gouvernance démocratique', 'Marché commun et intégration commerciale',
            'Stabilité macroéconomique et convergence régionale', 'Transformation agricole et sécurité alimentaire',
            'Gestion durable des ressources naturelles', 'Infrastructures régionales et connectivité',
            'Energie, climat et transition écologique', 'Genre, jeunesse et développement humain',
            'Modernisation institutionnelle et performance administrative', 'Digitalisation, données et redevabilité',
        ];

        foreach ($axesLabels as $a => $label) {
            $axe = Axe::firstOrCreate(
                ['papa_id' => $papa->id, 'code' => 'DEMO-A' . str_pad((string) ($a + 1), 2, '0', STR_PAD_LEFT)],
                [
                    'libelle' => $label,
                    'description' => 'DEMO - Axe stratégique : ' . $label,
                    'statut' => ['valide', 'en_validation', 'soumis'][$a % 3],
                    'ordre' => $a + 1,
                    'poids' => 10,
                    'taux_execution' => 25 + ($a * 5 % 70),
                    'date_debut' => "{$papa->annee}-01-15",
                    'date_fin' => "{$papa->annee}-12-15",
                    'departement_id' => $this->departements[$a % count($this->departements)]->id,
                    'responsable_id' => $this->users[$a % count($this->users)]->id,
                    'created_by' => $this->users[0]->id,
                ],
            );

            for ($p = 1; $p <= 3; $p++) {
                $produit = Produit::firstOrCreate(
                    ['axe_id' => $axe->id, 'code' => "DEMO-P{$a}-{$p}"],
                    [
                        'libelle' => "Produit {$p} - " . $this->produitLabel($a, $p),
                        'description' => 'DEMO - Résultat attendu lié à ' . $label,
                        'statut' => ['valide', 'soumis', 'en_validation'][$p % 3],
                        'ordre' => $p,
                        'poids' => 33.33,
                        'taux_execution' => 20 + (($a + $p) * 7 % 75),
                        'date_debut' => $axe->date_debut,
                        'date_fin' => $axe->date_fin,
                        'direction_id' => $this->directions[($a + $p) % count($this->directions)]->id,
                        'responsable_id' => $this->users[($a + $p) % count($this->users)]->id,
                        'created_by' => $this->users[0]->id,
                    ],
                );

                for ($s = 1; $s <= 2; $s++) {
                    $sp = SousProduit::firstOrCreate(
                        ['produit_id' => $produit->id, 'code' => "DEMO-SP{$a}-{$p}-{$s}"],
                        [
                            'libelle' => "Sous-produit {$s} - Livrable institutionnel consolidé",
                            'description' => 'DEMO - Livrable mesurable pour le suivi RBM/GAR.',
                            'statut' => ['valide', 'soumis', 'brouillon'][$s % 3],
                            'ordre' => $s,
                            'poids' => 50,
                            'taux_execution' => 15 + (($a + $p + $s) * 8 % 80),
                            'date_debut' => $produit->date_debut,
                            'date_fin' => $produit->date_fin,
                            'direction_id' => $produit->direction_id,
                            'responsable_id' => $produit->responsable_id,
                            'created_by' => $this->users[0]->id,
                        ],
                    );

                    for ($act = 1; $act <= 3; $act++) {
                        $statutAct = ['planifiee', 'en_cours', 'realisee', 'suspendue'][$act % 4];
                        // Étalement des dates sur l'année + ~25 % d'activités en retard (date_fin passée, statut ≠ realisee)
                        $moisDebut = ((($a + $p + $s + $act) % 10) + 1);
                        $dureeMois = (($act + $s) % 4) + 2;
                        $moisFin = min(12, $moisDebut + $dureeMois);
                        $dateDebut = sprintf('%d-%02d-01', $year, $moisDebut);
                        $dateFin = sprintf('%d-%02d-25', $year, $moisFin);

                        $taux = [0, 35, 100, 55][$act % 4];
                        // Dates réelles : étalées sur l'année pour alimenter le graphique d'évolution mensuelle
                        $moisRealisation = (($a * 3 + $p + $s + $act) % 11) + 1;
                        $dateDebutReelle = in_array($statutAct, ['en_cours', 'realisee'], true)
                            ? $dateDebut
                            : null;
                        $dateFinReelle = $statutAct === 'realisee'
                            ? sprintf('%d-%02d-%02d', $year, $moisRealisation, (($act * 7) % 28) + 1)
                            : null;

                        $activite = Activite::firstOrCreate(
                            ['sous_produit_id' => $sp->id, 'code' => "DEMO-ACT{$a}-{$p}-{$s}-{$act}"],
                            [
                                'libelle' => "Activité {$act} - " . $this->activityLabel($act),
                                'description' => 'DEMO - Activité de mise en œuvre avec jalons, risques et responsable.',
                                'statut' => $statutAct,
                                'ordre' => $act,
                                'poids' => 33.33,
                                'taux_execution' => $taux,
                                'date_debut' => $dateDebut,
                                'date_fin' => $dateFin,
                                'date_debut_reelle' => $dateDebutReelle,
                                'date_fin_reelle' => $dateFinReelle,
                                'niveau_risque' => ['faible', 'moyen', 'eleve', 'critique'][($a + $act) % 4],
                                'direction_id' => $sp->direction_id,
                                'responsable_id' => $sp->responsable_id,
                                'point_focal_id' => $this->users[($a + $p + $s + $act) % count($this->users)]->id,
                                'created_by' => $this->users[0]->id,
                            ],
                        );

                        for ($t = 1; $t <= 3; $t++) {
                            Tache::firstOrCreate(
                                ['activite_id' => $activite->id, 'code' => "DEMO-T{$a}-{$p}-{$s}-{$act}-{$t}"],
                                [
                                    'libelle' => "Tâche {$t} - " . $this->taskLabel($t),
                                    'description' => 'DEMO - Tâche opérationnelle avec responsable et calendrier.',
                                    'statut' => ['planifiee', 'en_cours', 'realisee', 'suspendue'][$t % 4],
                                    'ordre' => $t,
                                    'poids' => 33.33,
                                    'taux_execution' => [0, 45, 100, 20][$t % 4],
                                    'date_debut' => $activite->date_debut,
                                    'date_fin' => $activite->date_fin,
                                    'responsable_id' => $activite->responsable_id,
                                    'assigne_a_id' => $this->users[($a + $p + $s + $act + $t) % count($this->users)]->id,
                                    'created_by' => $this->users[0]->id,
                                ],
                            );
                        }
                    }

                    if (Indicateur::where('sous_produit_id', $sp->id)->where('code', 'like', 'DEMO-%')->count() < 2) {
                        for ($i = 1; $i <= 2; $i++) {
                            $ind = Indicateur::create([
                                'sous_produit_id' => $sp->id,
                                'code' => "DEMO-IND{$a}-{$p}-{$s}-{$i}",
                                'libelle' => $i === 1 ? 'Taux de réalisation du livrable' : 'Nombre de bénéficiaires institutionnels',
                                'definition' => 'DEMO - Indicateur RBM/GAR calculé à partir des rapports trimestriels.',
                                'type' => 'quantitatif',
                                'unite' => $i === 1 ? '%' : 'nombre',
                                'baseline' => 0,
                                'cible' => $i === 1 ? 100 : 250,
                                'date_baseline' => "{$year}-01-01",
                                'methode_calcul' => 'Valeur réalisée / cible annuelle.',
                                'frequence_collecte' => 'trimestrielle',
                                'source_donnees' => 'Rapports d’activité consolidés',
                                'responsable_id' => $sp->responsable_id,
                                'valeur_actuelle' => $i === 1 ? rand(20, 90) : rand(50, 220),
                                'taux_realisation' => rand(20, 90),
                                'tendance' => ['hausse', 'stable', 'baisse'][($a + $i) % 3],
                            ]);
                            $this->seedIndicatorValues($ind, $year);
                        }
                    }
                }
            }
        }

        return $papa;
    }

    protected function seedIndicatorValues(Indicateur $indicateur, int $year): void
    {
        foreach ([1, 2, 3, 4] as $q) {
            ValeurIndicateur::firstOrCreate(
                ['indicateur_id' => $indicateur->id, 'periode_libelle' => "T{$q} {$year}"],
                [
                    'date_observation' => "{$year}-" . str_pad((string) ($q * 3), 2, '0', STR_PAD_LEFT) . "-25",
                    'valeur' => min((float) ($indicateur->cible ?? 100), (float) ($indicateur->baseline ?? 0) + ($q * rand(8, 22))),
                    'commentaire' => 'DEMO - Mesure trimestrielle validée par le point focal.',
                    'source_verification' => 'Compte rendu trimestriel',
                    'saisi_par_id' => $this->users[$q % count($this->users)]->id,
                    'valide_at' => now()->subDays(20 - $q),
                    'valide_par_id' => $this->users[1]->id ?? null,
                ],
            );
        }
    }

    protected function seedBudget(int $year, Papa $papa): array
    {
        $exercices = [];
        foreach (range($year - 2, $year + 2) as $annee) {
            $exercices[$annee] = BudgetExercice::firstOrCreate(
                ['annee' => $annee],
                [
                    'libelle' => "DEMO-Budget institutionnel {$annee}",
                    'description' => "DEMO - Exercice budgétaire de démonstration {$annee}.",
                    'statut' => $annee < $year ? 'cloture' : ($annee === $year ? 'valide' : 'brouillon'),
                    'date_debut' => "{$annee}-01-01",
                    'date_fin' => "{$annee}-12-31",
                    'devise' => 'XAF',
                    'created_by' => $this->users[0]->id,
                ],
            );
        }

        $activites = Activite::where('code', 'like', 'DEMO-%')->get();
        $taches = Tache::where('code', 'like', 'DEMO-%')->get();
        $codes = range(60111, 60210);
        foreach (array_slice($codes, 0, 100) as $i => $code) {
            $total = (rand(25, 950) * 1_000_000);
            $ptf = $i % 3 === 0 ? (int) round($total * (rand(20, 65) / 100)) : 0;
            $ceeac = $total - $ptf;
            $activity = $activites[$i % max(1, $activites->count())] ?? null;
            $task = $taches[$i % max(1, $taches->count())] ?? null;
            $exercice = $exercices[$year];
            $parts = $this->nomenclature->decomposerCode((string) $code);

            $data = $this->nomenclature->normaliserLigne([
                'exercice_id' => $exercice->id,
                'budget_ligne_code' => (string) $code,
                'code_action' => (string) $code,
                'libelle' => $this->budgetLineLabel($i),
                'description' => 'DEMO - Ligne budgétaire réaliste associée au PAPA et au cycle IPSAS.',
                'nature' => 'depense',
                'type_budget' => ['fonctionnement', 'investissement', 'equipement', 'transfert'][$i % 4],
                'montant_total' => $total,
                'montant_ceeac_em' => $ceeac,
                'montant_ptf' => $ptf,
                'budget_annee_precedente' => max(0, $total - rand(1, 80) * 1_000_000),
                'realisation_annee_precedente' => max(0, $total - rand(1, 120) * 1_000_000),
                'taux_realisation_precedent' => rand(45, 98),
                'variation' => rand(-12, 25),
                'source_financement_id' => ($ptf > 0 ? $this->sources[($i % (count($this->sources) - 1)) + 1] : $this->sources[0])->id,
                'departement_id' => $this->departements[$i % count($this->departements)]->id,
                'direction_id' => $this->directions[$i % count($this->directions)]->id,
                'activite_id' => $activity?->id,
                'tache_id' => $task?->id,
                'statut' => ['brouillon', 'importe', 'controle', 'valide'][$i % 4],
                'ordre' => $i + 1,
                'observations' => 'DEMO - Ligne générée automatiquement.',
                'created_by' => $this->users[0]->id,
                'updated_by' => $this->users[1]->id,
            ], $exercice, $this->users[0]->id);

            $ligne = BudgetLigne::firstOrCreate(
                ['exercice_id' => $exercice->id, 'budget_ligne_code' => $parts['budget_ligne_code']],
                $data,
            );

            if ($i < 60) {
                $this->seedBudgetMovements($ligne, $i);
            }
        }

        foreach ($exercices as $exercice) {
            $exercice->recalculerTotaux();
        }

        return $exercices;
    }

    protected function seedBudgetMovements(BudgetLigne $ligne, int $i): void
    {
        $engage = round((float) $ligne->montant_total * (rand(15, 70) / 100), 2);
        $liquide = round($engage * (rand(40, 90) / 100), 2);
        $paye = round($liquide * (rand(35, 95) / 100), 2);
        $types = [['engagement', $engage], ['liquidation', $liquide], ['paiement', $paye]];
        $parentId = null;
        foreach ($types as $idx => [$type, $montant]) {
            $mvt = BudgetMouvement::firstOrCreate(
                ['ligne_id' => $ligne->id, 'type' => $type, 'reference' => "DEMO-MVT-{$ligne->id}-{$idx}"],
                [
                    'parent_mouvement_id' => $parentId,
                    'montant' => $montant,
                    'date_mouvement' => now()->subDays(90 - $i)->toDateString(),
                    'numero_piece' => "DEMO-PIECE-{$ligne->id}-{$idx}",
                    'beneficiaire_nom' => 'Prestataire institutionnel régional',
                    'beneficiaire_reference' => 'DEMO-BENEF',
                    'ordonnateur_id' => $this->users[3]->id ?? null,
                    'comptable_id' => $this->users[4]->id ?? null,
                    'mode_paiement' => $type === 'paiement' ? 'virement' : null,
                    'statut_mouvement' => 'valide',
                    'motif' => 'DEMO - Mouvement budgétaire IPSAS.',
                    'saisi_par_id' => $this->users[0]->id,
                    'valide_at' => now()->subDays(80 - $i),
                    'valide_par_id' => $this->users[1]->id ?? null,
                ],
            );
            $parentId = $mvt->id;
        }
    }

    protected function seedOperationalBudgets(Papa $papa): void
    {
        $targets = Activite::where('code', 'like', 'DEMO-%')->limit(40)->get()->merge(Tache::where('code', 'like', 'DEMO-%')->limit(60)->get());
        foreach ($targets as $i => $target) {
            $prevision = rand(15, 300) * 1_000_000;
            $engagement = (int) ($prevision * rand(20, 80) / 100);
            Budget::firstOrCreate(
                ['budgetable_type' => $target::class, 'budgetable_id' => $target->id, 'papa_id' => $papa->id, 'source' => $i % 4 === 0 ? 'partenaire' : 'ceeac'],
                [
                    'partenaire_id' => $i % 4 === 0 ? Partenaire::where('code', 'like', 'DEMO-%')->inRandomOrder()->value('id') : null,
                    'devise' => 'XAF',
                    'prevision' => $prevision,
                    'engagement' => $engagement,
                    'consommation' => (int) ($engagement * rand(35, 95) / 100),
                    'reste_a_engager' => max(0, $prevision - $engagement),
                    'observations' => 'DEMO - Budget opérationnel lié au PAPA.',
                ],
            );
        }
    }

    protected function seedValidationsAndAlerts(Papa $papa): void
    {
        foreach (Axe::where('papa_id', $papa->id)->limit(10)->get() as $i => $axe) {
            Validation::firstOrCreate(
                ['validable_type' => Axe::class, 'validable_id' => $axe->id, 'etape' => 'revue_technique'],
                [
                    'decision' => ['approuve', 'en_attente', 'renvoye'][$i % 3],
                    'demandeur_id' => $this->users[0]->id,
                    'valideur_id' => $this->users[1]->id,
                    'decide_at' => $i % 3 === 1 ? null : now()->subDays($i),
                    'commentaire' => 'DEMO - Validation institutionnelle du cycle PAPA.',
                    'donnees_avant' => ['statut' => 'soumis'],
                    'donnees_apres' => ['statut' => $axe->statut],
                ],
            );
            Alerte::firstOrCreate(
                ['alertable_type' => Axe::class, 'alertable_id' => $axe->id, 'titre' => 'DEMO-Alerte de pilotage ' . ($i + 1)],
                [
                    'niveau' => ['info', 'attention', 'critique'][$i % 3],
                    'categorie' => ['retard', 'derive_budgetaire', 'sous_performance'][$i % 3],
                    'message' => 'DEMO - Alerte issue du suivi automatique des délais, budgets et performances.',
                    'contexte' => ['papa_id' => $papa->id, 'source' => 'demo'],
                    'automatique' => true,
                    'statut' => ['ouverte', 'en_traitement', 'resolue'][$i % 3],
                    'assignee_a_id' => $this->users[($i + 5) % count($this->users)]->id,
                ],
            );
        }
    }

    protected function seedImports(BudgetExercice $exercice): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $import = BudgetImport::firstOrCreate(
                ['fichier_nom' => "DEMO-import-budget-{$i}.xlsx"],
                [
                    'exercice_id' => $exercice->id,
                    'chemin_stockage' => "budget-imports/DEMO-import-budget-{$i}.xlsx",
                    'taille_octets' => rand(80_000, 350_000),
                    'hash_sha256' => hash('sha256', "demo-import-{$i}"),
                    'feuille_source' => ['Budget', 'PAP', 'Contributions'][$i % 3],
                    'type_donnees' => 'budget',
                    'statut' => ['reussi', 'prevu', 'echec', 'reussi', 'a_corriger'][$i - 1],
                    'nb_lignes_lues' => rand(120, 650),
                    'nb_lignes_creees' => rand(40, 220),
                    'nb_lignes_mises_a_jour' => rand(0, 30),
                    'nb_erreurs' => $i === 3 ? 8 : rand(0, 3),
                    'journal' => ['demo' => true, 'mapping' => 'budget_malabo_2026'],
                    'options' => ['dry_run' => $i === 2, 'feuilles' => ['Budget']],
                    'execute_par_id' => $this->users[0]->id,
                    'execute_at' => now()->subDays($i * 3),
                ],
            );

            if ($import->nb_erreurs > 0) {
                for ($e = 1; $e <= min(5, $import->nb_erreurs); $e++) {
                    BudgetImportErreur::firstOrCreate(
                        ['import_id' => $import->id, 'ligne' => $e + 4, 'colonne' => 'Prévisions 2026'],
                        [
                            'feuille' => $import->feuille_source,
                            'valeur_fautive' => '#REF!',
                            'gravite' => $e === 1 ? 'critique' : 'erreur',
                            'regle' => 'format_invalide',
                            'message' => 'DEMO - Montant non exploitable ou formule Excel invalide.',
                            'correction_suggeree' => 'Remplacer la formule par une valeur numérique validée.',
                        ],
                    );
                }
            }
        }

        BudgetImportMapping::firstOrCreate(
            ['libelle' => 'DEMO-Mapping Budget Malabo 2026'],
            [
                'user_id' => $this->users[0]->id,
                'type_donnees' => 'budget',
                'mapping' => ['Titre' => 'titre_code', 'Chap.' => 'chapitre_code', 'Code Action' => 'code_action', 'Prévisions 2026' => 'previsions'],
                'partage' => true,
            ],
        );
    }

    protected function seedNotifications(): void
    {
        foreach (array_slice($this->users, 0, 20) as $i => $user) {
            DB::table('notifications')->updateOrInsert(
                ['id' => (string) Str::uuid()],
                [
                    'type' => 'DemoBudgetNotification',
                    'notifiable_type' => User::class,
                    'notifiable_id' => $user->id,
                    'data' => json_encode(['titre' => 'DEMO - Validation requise', 'message' => 'Une action PAPA ou budgétaire attend votre validation.']),
                    'read_at' => $i % 3 === 0 ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    protected function seedAudit(int $year): void
    {
        $plan = AuditPlan::firstOrCreate(
            ['annee' => $year],
            [
                'libelle' => "DEMO-Plan annuel d'audit interne {$year}",
                'description' => 'DEMO - Couverture des risques budgétaires, RBM/GAR et sécurité des imports.',
                'orientation_strategique' => 'Contrôle interne, conformité budgétaire, qualité des données.',
                'statut' => 'valide',
                'date_debut' => "{$year}-01-15",
                'date_fin' => "{$year}-12-15",
                'valide_par_id' => $this->users[1]->id ?? null,
                'valide_at' => now()->subDays(40),
                'created_by' => $this->users[0]->id,
            ],
        );

        foreach (range(1, 4) as $i) {
            $mission = AuditMission::firstOrCreate(
                ['code' => "DEMO-AUD-{$year}-{$i}"],
                [
                    'plan_id' => $plan->id,
                    'titre' => ['Audit des imports Excel', 'Audit du cycle budgétaire', 'Audit RBM/GAR', 'Audit sécurité et accès'][$i - 1],
                    'objectifs' => 'DEMO - Evaluer la maturité du contrôle interne et la fiabilité des données.',
                    'perimetre' => 'Départements, services, budget, PAPA et tableaux de bord.',
                    'type' => ['conformite', 'organisationnel', 'financier', 'systeme_information'][$i - 1],
                    'priorite' => ['critique', 'haute', 'moyenne', 'haute'][$i - 1],
                    'statut' => ['planifiee', 'en_cours', 'projet_rapport', 'cloturee'][$i - 1],
                    'date_debut_prevue' => "{$year}-0{$i}-01",
                    'date_fin_prevue' => "{$year}-0" . ($i + 2) . "-25",
                    'chef_mission_id' => $this->users[7]->id ?? null,
                    'departement_audite_id' => $this->departements[$i % count($this->departements)]->id,
                    'direction_auditee_id' => $this->directions[$i % count($this->directions)]->id,
                    'lettre_mission' => 'DEMO - Lettre de mission validée.',
                    'synthese' => 'DEMO - Synthèse exécutive de la mission.',
                    'created_by' => $this->users[0]->id,
                ],
            );

            $constat = AuditConstat::firstOrCreate(
                ['mission_id' => $mission->id, 'code' => "DEMO-C{$i}"],
                [
                    'libelle' => 'Renforcement requis du contrôle de cohérence',
                    'description' => 'DEMO - Les contrôles existent mais doivent être systématisés dans les imports.',
                    'preuves' => 'Rapports import, journaux applicatifs, échantillons de lignes.',
                    'gravite' => ['critique', 'majeur', 'moyen', 'mineur'][$i - 1],
                    'nature' => ['controle_insuffisant', 'non_conformite', 'risque', 'inefficience'][$i - 1],
                    'cause_racine' => 'Hétérogénéité des fichiers source.',
                    'impact' => 'Risque de retard de consolidation et de correction manuelle.',
                    'saisi_par_id' => $this->users[7]->id ?? null,
                ],
            );

            $rec = AuditRecommandation::firstOrCreate(
                ['constat_id' => $constat->id, 'code' => "DEMO-R{$i}"],
                [
                    'libelle' => 'Formaliser un contrôle automatisé',
                    'action_proposee' => 'Mettre en place un contrôle automatisé et un rapport d’anomalies.',
                    'priorite' => ['urgente', 'haute', 'moyenne', 'basse'][$i - 1],
                    'responsable_mise_en_oeuvre_id' => $this->users[8]->id ?? null,
                    'date_echeance' => now()->addMonths($i),
                    'statut' => ['ouverte', 'en_cours', 'verifiee', 'mise_en_oeuvre'][$i - 1],
                    'pourcentage_avancement' => [0, 45, 100, 60][$i - 1],
                    'saisi_par_id' => $this->users[7]->id ?? null,
                ],
            );

            AuditSuiviRecommandation::firstOrCreate(
                ['recommandation_id' => $rec->id, 'date_suivi' => now()->subDays($i * 5)->toDateString()],
                [
                    'etat_avancement' => ['non_demarre', 'en_cours', 'realise', 'bloque'][$i - 1],
                    'pourcentage' => [0, 45, 100, 60][$i - 1],
                    'actions_realisees' => 'DEMO - Actions de mise en conformité engagées.',
                    'actions_restantes' => 'Formalisation et validation institutionnelle.',
                    'commentaire' => 'DEMO - Suivi périodique de recommandation.',
                    'suivi_par_id' => $this->users[7]->id ?? null,
                ],
            );
        }
    }

    protected function seedGeneratedReports(int $year): void
    {
        foreach (['budget_consolide', 'matrice_rbm', 'rapport_import', 'audit_interne', 'dashboard_executif'] as $i => $key) {
            GeneratedReport::firstOrCreate(
                ['code_verification' => 'DEMO-RPT-' . $year . '-' . $i],
                [
                    'report_key' => 'demo_' . $key,
                    'categorie' => ['budget', 'performance', 'audit', 'analytique'][$i % 4],
                    'titre' => 'DEMO - ' . Str::headline($key),
                    'description' => 'Rapport de démonstration généré pour les tableaux de bord et exports.',
                    'filtres' => ['annee' => $year, 'demo' => true],
                    'chemin_stockage' => "private/reports/demo/{$key}.pdf",
                    'nom_fichier' => "DEMO-{$key}-{$year}.pdf",
                    'taille_octets' => rand(120_000, 2_500_000),
                    'hash_sha256' => hash('sha256', "demo-report-{$key}-{$year}"),
                    'format' => $i % 2 === 0 ? 'pdf' : 'xlsx',
                    'nb_pages' => rand(4, 28),
                    'signe_numeriquement' => $i % 2 === 0,
                    'signature_hash' => hash('sha256', "signature-{$key}"),
                    'genere_par_id' => $this->users[0]->id,
                    'genere_at' => now()->subDays($i + 1),
                    'nb_telechargements' => rand(0, 12),
                    'archive_ged' => false,
                    'statut' => 'pret',
                ],
            );
        }
    }

    protected function produitLabel(int $axeIndex, int $produitIndex): string
    {
        return ['Cadre stratégique renforcé', 'Outils régionaux opérationnels', 'Mécanismes de coordination consolidés'][($axeIndex + $produitIndex) % 3];
    }

    protected function activityLabel(int $index): string
    {
        return ['Organiser les concertations techniques', 'Produire les livrables de référence', 'Déployer le dispositif de suivi'][$index - 1];
    }

    protected function taskLabel(int $index): string
    {
        return ['Préparer les termes de référence', 'Mobiliser les parties prenantes', 'Valider et archiver les livrables'][$index - 1];
    }

    protected function budgetLineLabel(int $i): string
    {
        return [
            'Fournitures de bureau et consommables',
            'Missions institutionnelles régionales',
            'Ateliers de validation technique',
            'Assistance technique spécialisée',
            'Equipements informatiques et licences',
            'Communication institutionnelle',
            'Etudes, évaluations et enquêtes',
            'Maintenance des plateformes numériques',
        ][$i % 8];
    }
}
