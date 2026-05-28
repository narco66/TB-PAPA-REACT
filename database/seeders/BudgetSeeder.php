<?php

namespace Database\Seeders;

use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetSourceFinancement;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder du Budget Exercice 2026 de la Commission de la CEEAC.
 * Données réelles extraites du document budgétaire institutionnel
 * (annexes N°1 et N°2 du Budget 2026).
 */
class BudgetSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSourcesFinancement();
        $this->seedExercice2026();
        $this->seedExercice2025();
    }

    protected function seedSourcesFinancement(): void
    {
        // Sources internes (États Membres)
        $sources = [
            ['code' => 'CEEAC-EM', 'libelle' => 'CEEAC — Contributions des États Membres', 'type' => 'interne', 'categorie' => 'etat_membre'],
        ];

        foreach ($sources as $s) {
            BudgetSourceFinancement::firstOrCreate(['code' => $s['code']], array_merge($s, ['actif' => true]));
        }

        // Sources externes (PTF) — alignées sur les partenaires institutionnels
        $partenaires = [
            ['code' => 'BAD', 'libelle' => 'Banque Africaine de Développement', 'type' => 'multilateral'],
            ['code' => 'UE', 'libelle' => 'Union Européenne (incl. ITC, ONUDI)', 'type' => 'multilateral'],
            ['code' => 'AU-AUDA-NEPAD', 'libelle' => 'Union Africaine / AUDA-NEPAD / AU-BIRA', 'type' => 'multilateral'],
            ['code' => 'BM', 'libelle' => 'Banque Mondiale', 'type' => 'multilateral'],
            ['code' => 'ONUDI', 'libelle' => 'Organisation des Nations Unies pour le Développement Industriel', 'type' => 'multilateral'],
            ['code' => 'ONU-AC', 'libelle' => 'ONU/UNOCA/UNOP/ONUSIDA/ONU-Habitat', 'type' => 'multilateral'],
            ['code' => 'CONSORTIUM-PTF', 'libelle' => 'Consortium PTF', 'type' => 'multilateral'],
            ['code' => 'AGRA', 'libelle' => 'AGRA — Alliance for a Green Revolution in Africa', 'type' => 'multilateral'],
            ['code' => 'CDC-AFRIQUE', 'libelle' => 'CDC Afrique', 'type' => 'multilateral'],
            ['code' => 'FAO', 'libelle' => 'Organisation des Nations Unies pour l\'alimentation et l\'agriculture', 'type' => 'multilateral'],
            ['code' => 'CARD', 'libelle' => 'CARD — Coalition for African Rice Development', 'type' => 'multilateral'],
        ];

        foreach ($partenaires as $p) {
            BudgetSourceFinancement::firstOrCreate(
                ['code' => $p['code']],
                ['libelle' => $p['libelle'], 'type' => 'externe', 'categorie' => $p['type'], 'actif' => true],
            );
        }
    }

    protected function seedExercice2025(): void
    {
        $sg = User::role('secretaire_general')->first();

        $exercice = BudgetExercice::firstOrCreate(
            ['annee' => 2025],
            [
                'libelle' => 'Budget de l\'exercice 2025 de la Commission CEEAC',
                'description' => 'Exercice budgétaire 2025 (réalisations + clôture).',
                'statut' => 'cloture',
                'devise' => 'XAF',
                'date_debut' => '2025-01-01',
                'date_fin' => '2025-12-31',
                'created_by' => $sg?->id,
            ],
        );

        $exercice->recalculerTotaux();
    }

    protected function seedExercice2026(): void
    {
        $president = User::role('president')->first();
        $sg = User::role('secretaire_general')->first();

        $exercice = BudgetExercice::firstOrCreate(
            ['annee' => 2026],
            [
                'libelle' => 'Budget de l\'exercice 2026 de la Commission CEEAC',
                'description' => 'Budget institutionnel CEEAC pour l\'exercice 2026. Total : 40 305 795 803 FCFA dont 26 275 514 803 CEEAC-EM et 14 030 281 000 PTF.',
                'statut' => 'valide',
                'devise' => 'XAF',
                'date_debut' => '2026-01-01',
                'date_fin' => '2026-12-31',
                'valide_par_id' => $president?->id,
                'valide_at' => '2025-12-15 10:00:00',
                'created_by' => $sg?->id,
            ],
        );

        // Référentiels
        $srcCeeac = BudgetSourceFinancement::where('code', 'CEEAC-EM')->first();
        $srcUE = BudgetSourceFinancement::where('code', 'UE')->first();
        $srcBAD = BudgetSourceFinancement::where('code', 'BAD')->first();
        $srcBM = BudgetSourceFinancement::where('code', 'BM')->first();
        $srcAU = BudgetSourceFinancement::where('code', 'AU-AUDA-NEPAD')->first();
        $srcONUDI = BudgetSourceFinancement::where('code', 'ONUDI')->first();

        // === RECETTES (Annexe N°1) ===
        $this->ligne($exercice, [
            'titre_code' => 'Titre 1', 'chapitre_code' => '72', 'article_code' => '7210',
            'libelle' => 'Contributions des États membres (Recettes internes)',
            'nature' => 'recette', 'type_budget' => 'recette_interne',
            'montant_total' => 26_275_514_803, 'montant_ceeac_em' => 26_275_514_803, 'montant_ptf' => 0,
            'budget_annee_precedente' => 26_275_486_284,
            'realisation_annee_precedente' => 11_832_586_233,
            'taux_realisation_precedent' => 45,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $contributions = [
            ['72101', 'République d\'Angola', 2_890_306_628],
            ['72102', 'République du Burundi', 1_313_775_740],
            ['72103', 'République du Cameroun', 2_890_306_628],
            ['72104', 'République Centrafricaine', 1_313_775_740],
            ['72105', 'République du Congo', 2_890_306_628],
            ['72106', 'République Démocratique du Congo', 2_627_551_480],
            ['72107', 'République Gabonaise', 2_890_306_628],
            ['72108', 'République de Guinée Equatoriale', 2_890_306_628],
            ['72109', 'République Rwandaise', 2_627_551_480],
            ['72110', 'Sao Tomé et Principe', 1_313_775_740],
            ['72111', 'République du Tchad', 2_627_551_480],
        ];
        foreach ($contributions as [$code, $libelle, $montant]) {
            $this->ligne($exercice, [
                'titre_code' => 'Titre 1', 'chapitre_code' => '72', 'article_code' => '7210', 'paragraphe_code' => $code,
                'code_action' => $code, 'libelle' => "Contribution — {$libelle}",
                'nature' => 'recette', 'type_budget' => 'recette_interne',
                'montant_total' => $montant, 'montant_ceeac_em' => $montant, 'montant_ptf' => 0,
                'source_financement_id' => $srcCeeac?->id,
            ]);
        }

        $this->ligne($exercice, [
            'titre_code' => 'Titre 2', 'chapitre_code' => '74', 'article_code' => '741', 'paragraphe_code' => '7411',
            'libelle' => 'Dons des institutions internationales (Recettes externes — PTF)',
            'nature' => 'recette', 'type_budget' => 'recette_externe',
            'montant_total' => 14_030_281_000, 'montant_ceeac_em' => 0, 'montant_ptf' => 14_030_281_000,
            'budget_annee_precedente' => 11_846_247_097,
            'realisation_annee_precedente' => 2_840_427_566,
            'taux_realisation_precedent' => 24,
            'variation' => 18.4,
        ]);

        $donsPtf = [
            ['BAD', 9_355_786_000, $srcBAD],
            ['UE (ITC, ONUDI)', 1_280_000_000, $srcUE],
            ['UA / AUDA-NEPAD / AU-BIRA', 755_000_000, $srcAU],
            ['Banque Mondiale', 750_000_000, $srcBM],
            ['ONUDI', 715_275_000, $srcONUDI],
            ['ONU/UNOCA/UNOP/ONUSIDA/ONU-Habitat', 629_020_000, null],
            ['Consortium PTF', 195_000_000, null],
            ['AGRA', 115_000_000, null],
            ['CDC Afrique', 100_000_000, null],
            ['FAO', 85_000_000, null],
            ['CARD', 50_000_000, null],
        ];
        foreach ($donsPtf as [$libelle, $montant, $src]) {
            $this->ligne($exercice, [
                'titre_code' => 'Titre 2', 'chapitre_code' => '74', 'article_code' => '741', 'paragraphe_code' => '7411',
                'libelle' => "Dons projets — {$libelle}",
                'nature' => 'recette', 'type_budget' => 'recette_externe',
                'montant_total' => $montant, 'montant_ceeac_em' => 0, 'montant_ptf' => $montant,
                'source_financement_id' => $src?->id,
            ]);
        }

        // === DÉPENSES — récapitulatifs principaux (Annexe N°2) ===
        $this->ligne($exercice, [
            'titre_code' => 'Titre 1', 'chapitre_code' => '67', 'article_code' => '671',
            'code_action' => '671', 'libelle' => 'Charges financières — Intérêts et frais sur la dette',
            'nature' => 'depense', 'type_budget' => 'dette',
            'montant_total' => 10_000_000, 'montant_ceeac_em' => 10_000_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 20_000_000,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $this->ligne($exercice, [
            'titre_code' => 'Titre 2', 'chapitre_code' => '66', 'article_code' => '66',
            'code_action' => '66', 'libelle' => 'Dépenses de personnel',
            'nature' => 'depense', 'type_budget' => 'fonctionnement',
            'montant_total' => 11_586_264_803, 'montant_ceeac_em' => 11_586_264_803, 'montant_ptf' => 0,
            'budget_annee_precedente' => 11_326_135_694,
            'realisation_annee_precedente' => 9_836_719_222,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $this->ligne($exercice, [
            'titre_code' => 'Titre 3', 'chapitre_code' => '60-61', 'code_action' => '60-61',
            'libelle' => 'Dépenses de biens et services (Achats + Acquisitions)',
            'nature' => 'depense', 'type_budget' => 'fonctionnement',
            'montant_total' => 1_871_250_000, 'montant_ceeac_em' => 1_871_250_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 1_992_500_000,
            'realisation_annee_precedente' => 1_339_246_332,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $this->ligne($exercice, [
            'chapitre_code' => '64', 'code_action' => '64',
            'libelle' => 'Dépenses de transferts de fonctionnement (Bureaux de liaison)',
            'nature' => 'depense', 'type_budget' => 'transfert',
            'montant_total' => 210_000_000, 'montant_ceeac_em' => 210_000_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 1_100_000_000,
            'realisation_annee_precedente' => 560_000_000,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        // === PILIERS du Plan Annuel de Performance (montants en FCFA — milliers du PDF × 1000) ===
        $piliers = [
            [1, '201', 'PILIER 1 — INTÉGRATION POLITIQUE, PAIX ET SÉCURITÉ', 1_750_000_000, 1_750_000_000, 0],
            [2, '202', 'PILIER 2 — INTÉGRATION ÉCONOMIQUE ET COMMERCIALE', 3_095_000_000, 1_750_000_000, 1_345_000_000],
            [3, '203', 'PILIER 3 — AMÉNAGEMENT DU TERRITOIRE, INFRASTRUCTURES ET INTÉGRATION PHYSIQUE', 11_172_061_000, 1_000_000_000, 10_172_061_000],
            [4, '204', 'PILIER 4 — INTÉGRATION ENVIRONNEMENTALE, AGRICULTURE, TRANSFORMATION', 2_907_120_000, 1_000_000_000, 1_907_120_000],
            [5, '205', 'PILIER 5 — PROMOTION DU GENRE, INTÉGRATION HUMAINE ET SOCIALE', 1_956_100_000, 1_350_000_000, 606_100_000],
            [6, '206', 'PILIER 6 — POURSUITE DE LA RÉFORME ET RENFORCEMENT INSTITUTIONNEL', 1_485_000_000, 1_485_000_000, 0],
        ];
        foreach ($piliers as [$pilier, $code, $libelle, $total, $ceeac, $ptf]) {
            $this->ligne($exercice, [
                'code_action' => $code, 'libelle' => $libelle,
                'nature' => 'depense', 'type_budget' => 'investissement',
                'pilier' => $pilier,
                'montant_total' => $total, 'montant_ceeac_em' => $ceeac, 'montant_ptf' => $ptf,
                'source_financement_id' => $ceeac > 0 ? $srcCeeac?->id : null,
            ]);
        }

        // === Autres dépenses ===
        $this->ligne($exercice, [
            'chapitre_code' => '209', 'code_action' => '209',
            'libelle' => 'Autres dépenses des programmes (Assises statutaires + Mission financement durable + Médiation)',
            'nature' => 'depense', 'type_budget' => 'autre',
            'montant_total' => 1_525_000_000, 'montant_ceeac_em' => 1_525_000_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 975_000_000,
            'realisation_annee_precedente' => 661_112_739,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $this->ligne($exercice, [
            'chapitre_code' => '63', 'article_code' => '631',
            'code_action' => '631', 'libelle' => 'Dotations spéciales aux institutions spécialisées (PEAC, CRESMAC, CIC, etc.)',
            'nature' => 'depense', 'type_budget' => 'dotation',
            'montant_total' => 1_997_000_000, 'montant_ceeac_em' => 1_997_000_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 2_302_000_000,
            'realisation_annee_precedente' => 1_389_903_179,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        $this->ligne($exercice, [
            'chapitre_code' => '21-24', 'code_action' => '21-24',
            'libelle' => 'Dépenses d\'équipement (Informatique, mobilier, transport)',
            'nature' => 'depense', 'type_budget' => 'equipement',
            'montant_total' => 741_000_000, 'montant_ceeac_em' => 741_000_000, 'montant_ptf' => 0,
            'budget_annee_precedente' => 1_014_350_590,
            'realisation_annee_precedente' => 90_143_982,
            'source_financement_id' => $srcCeeac?->id,
        ]);

        // Recalcul des totaux de l'exercice
        $exercice->recalculerTotaux();
    }

    protected function ligne(BudgetExercice $exercice, array $attrs): BudgetLigne
    {
        return BudgetLigne::create(array_merge([
            'exercice_id' => $exercice->id,
            'statut' => 'valide',
        ], $attrs));
    }
}
