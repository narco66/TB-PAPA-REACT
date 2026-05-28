<?php

namespace Database\Seeders;

use App\Models\Activite;
use App\Models\Axe;
use App\Models\Budget;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\Partenaire;
use App\Models\Produit;
use App\Models\SousProduit;
use App\Models\Tache;
use App\Models\User;
use App\Services\Rbm\RecalculAvancementService;
use Illuminate\Database\Seeder;

class PapaDemoSeeder extends Seeder
{
    /**
     * PAPA 2026 de démonstration — chaîne RBM CEEAC complète :
     * Axe → Produit → SousProduit → Activité → Tâche.
     */
    public function run(): void
    {
        $president = User::role('president')->first();
        $sg = User::role('secretaire_general')->first();
        $daec = Departement::where('code', 'DAEC')->first();
        $diem = Departement::where('code', 'DIEM')->first();
        $dsi = Direction::where('code', 'DSI')->first();

        $papa = Papa::firstOrCreate(
            ['annee' => 2026, 'version' => '1.0'],
            [
                'libelle' => 'Plan d\'Action Prioritaire Annuel 2026',
                'description' => 'PAPA de référence de la Commission de la CEEAC pour l\'exercice 2026.',
                'perimetre_institutionnel' => '6 Départements techniques, 5 Directions d\'appui.',
                'statut' => Papa::STATUT_VALIDE,
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',
                'date_validation' => '2025-12-15 10:00:00',
                'valide_par_id' => $president?->id,
                'created_by' => $sg?->id,
            ],
        );

        // === AXE 1 : Marché Commun ===
        $axe1 = Axe::create([
            'papa_id' => $papa->id,
            'libelle' => 'Accélération du Marché Commun de l\'Afrique Centrale',
            'description' => 'Mise en œuvre des instruments du Marché Commun.',
            'statut' => 'valide',
            'poids' => 35,
            'departement_id' => $daec?->id,
            'responsable_id' => $daec?->commissaire_id,
            'date_debut' => '2026-01-15',
            'date_fin' => '2026-12-15',
        ]);
        $this->arborer($axe1, [
            ['libelle' => 'Instrument TVA harmonisée adopté', 'sps' => [
                ['libelle' => 'Projet d\'instrument TVA rédigé et validé', 'activites' => [
                    ['libelle' => 'Rédaction du projet d\'instrument TVA', 'taches' => [
                        ['libelle' => 'Bibliographie et benchmark international', 'taux' => 100],
                        ['libelle' => 'Rédaction de la version initiale', 'taux' => 80],
                        ['libelle' => 'Validation interne SG', 'taux' => 40],
                    ]],
                    ['libelle' => 'Conférence des Ministres de validation', 'taches' => [
                        ['libelle' => 'Logistique et organisation', 'taux' => 60],
                        ['libelle' => 'Communiqué final', 'taux' => 0],
                    ]],
                ]],
                ['libelle' => 'Ratifications des États membres', 'activites' => [
                    ['libelle' => 'Missions de plaidoyer auprès des États', 'taches' => [
                        ['libelle' => 'Préparation des dossiers de plaidoyer', 'taux' => 50],
                    ]],
                ]],
            ]],
        ]);

        // === AXE 2 : Infrastructures ===
        $axe2 = Axe::create([
            'papa_id' => $papa->id,
            'libelle' => 'Programme d\'infrastructures routières d\'intégration',
            'description' => 'PDCT-AC.',
            'statut' => 'valide',
            'poids' => 40,
            'departement_id' => $diem?->id,
            'responsable_id' => $diem?->commissaire_id,
            'date_debut' => '2026-02-01',
            'date_fin' => '2026-11-30',
        ]);
        $this->arborer($axe2, [
            ['libelle' => '300 km de routes financées', 'sps' => [
                ['libelle' => 'Études techniques préalables', 'activites' => [
                    ['libelle' => 'Mobilisation des bureaux d\'études', 'taches' => [
                        ['libelle' => 'Appel d\'offres bureaux d\'études', 'taux' => 100],
                        ['libelle' => 'Sélection et notification', 'taux' => 70],
                    ]],
                ]],
                ['libelle' => 'Conventions de financement signées', 'activites' => [
                    ['libelle' => 'Négociation BAD', 'taches' => []],
                ]],
            ]],
        ]);

        // === AXE 3 : Modernisation SI ===
        $axe3 = Axe::create([
            'papa_id' => $papa->id,
            'libelle' => 'Modernisation des systèmes d\'information',
            'description' => 'Transformation numérique 2025-2027.',
            'statut' => 'valide',
            'poids' => 25,
            'responsable_id' => $dsi?->directeur_id,
            'date_debut' => '2026-03-01',
            'date_fin' => '2026-12-31',
        ]);
        $this->arborer($axe3, [
            ['libelle' => 'TB-PAPA-CEEAC opérationnel', 'sps' => [
                ['libelle' => 'Migration et déploiement', 'activites' => [
                    ['libelle' => 'Migration des données historiques', 'taches' => [
                        ['libelle' => 'Audit des données existantes', 'taux' => 100],
                        ['libelle' => 'Scripts ETL', 'taux' => 75],
                        ['libelle' => 'Validation utilisateurs', 'taux' => 30],
                    ]],
                    ['libelle' => 'Formation des utilisateurs', 'taches' => []],
                ]],
            ]],
        ]);

        // Recalcul global du PAPA après création
        app(RecalculAvancementService::class)->recalculerPapa($papa->id);

        // === Budgets sur les Axes ===
        foreach ([$axe1, $axe2, $axe3] as $axe) {
            Budget::create([
                'budgetable_type' => Axe::class,
                'budgetable_id' => $axe->id,
                'papa_id' => $papa->id,
                'source' => 'ceeac',
                'devise' => 'XAF',
                'prevision' => rand(100_000_000, 500_000_000),
                'engagement' => rand(50_000_000, 200_000_000),
                'consommation' => rand(20_000_000, 100_000_000),
            ]);
            $partenaire = Partenaire::inRandomOrder()->first();
            if ($partenaire) {
                Budget::create([
                    'budgetable_type' => Axe::class,
                    'budgetable_id' => $axe->id,
                    'papa_id' => $papa->id,
                    'source' => 'partenaire',
                    'partenaire_id' => $partenaire->id,
                    'devise' => 'XAF',
                    'prevision' => rand(200_000_000, 800_000_000),
                    'engagement' => rand(100_000_000, 400_000_000),
                    'consommation' => rand(30_000_000, 200_000_000),
                ]);
            }
        }
    }

    /**
     * Construit récursivement Produits → Sous-Produits → Activités → Tâches.
     * Crée également un indicateur par sous-produit.
     */
    protected function arborer(Axe $axe, array $produits): void
    {
        foreach ($produits as $pdef) {
            $produit = Produit::create([
                'axe_id' => $axe->id,
                'libelle' => $pdef['libelle'],
                'statut' => 'valide',
                'poids' => 100,
                'date_debut' => $axe->date_debut,
                'date_fin' => $axe->date_fin,
            ]);

            foreach ($pdef['sps'] ?? [] as $spdef) {
                $sp = SousProduit::create([
                    'produit_id' => $produit->id,
                    'libelle' => $spdef['libelle'],
                    'statut' => 'valide',
                    'poids' => 100,
                    'date_debut' => $produit->date_debut,
                    'date_fin' => $produit->date_fin,
                ]);

                // Indicateur démo par sous-produit
                Indicateur::create([
                    'sous_produit_id' => $sp->id,
                    'code' => 'IND-' . $sp->id,
                    'libelle' => 'Indicateur de suivi : ' . substr($sp->libelle, 0, 60),
                    'type' => 'quantitatif',
                    'unite' => '%',
                    'baseline' => 0,
                    'cible' => 100,
                    'frequence_collecte' => 'trimestrielle',
                    'source_donnees' => 'Rapports trimestriels',
                    'valeur_actuelle' => rand(10, 60),
                    'taux_realisation' => rand(10, 60),
                    'tendance' => 'hausse',
                ]);

                foreach ($spdef['activites'] ?? [] as $adef) {
                    $activite = Activite::create([
                        'sous_produit_id' => $sp->id,
                        'libelle' => $adef['libelle'],
                        'statut' => 'en_cours',
                        'poids' => 100,
                        'date_debut' => $sp->date_debut,
                        'date_fin' => $sp->date_fin,
                        'niveau_risque' => collect(['faible', 'moyen', 'eleve'])->random(),
                    ]);

                    foreach ($adef['taches'] ?? [] as $tdef) {
                        Tache::create([
                            'activite_id' => $activite->id,
                            'libelle' => $tdef['libelle'],
                            'taux_execution' => $tdef['taux'] ?? rand(0, 80),
                            'statut' => ($tdef['taux'] ?? 0) >= 100 ? 'realisee' : (($tdef['taux'] ?? 0) > 0 ? 'en_cours' : 'planifiee'),
                            'poids' => 100,
                            'date_debut' => $activite->date_debut,
                            'date_fin' => $activite->date_fin,
                        ]);
                    }
                }
            }
        }
    }
}
