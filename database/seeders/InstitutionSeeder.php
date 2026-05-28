<?php

namespace Database\Seeders;

use App\Models\Departement;
use App\Models\Direction;
use App\Models\Partenaire;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstitutionSeeder extends Seeder
{
    /**
     * Crée la structure institutionnelle CEEAC + comptes de démonstration.
     */
    public function run(): void
    {
        // === 1. Cabinet ===
        $president = $this->creerUtilisateur('president@ceeac.org', 'Mahamat Saleh ANNADIF', 'Président de la Commission', 'PRES-001', 'president');
        $vp = $this->creerUtilisateur('vp@ceeac.org', 'Marc Antoine ESSO', 'Vice-Président de la Commission', 'VP-001', 'vice_president');
        $sg = $this->creerUtilisateur('sg@ceeac.org', 'Andre MBATA', 'Secrétaire Général', 'SG-001', 'secretaire_general');

        // === 2. Départements techniques (sous autorité des Commissaires) ===
        $departements = [
            ['code' => 'DAEC', 'libelle' => 'Aménagement, Économie et Commerce', 'commissaire' => ['email' => 'commissaire.aec@ceeac.org', 'name' => 'Hervé MARTIAL ASSANGO']],
            ['code' => 'DAPSAR', 'libelle' => 'Agriculture, Pêche, Sécurité Alimentaire et Réformes Rurales', 'commissaire' => ['email' => 'commissaire.apsar@ceeac.org', 'name' => 'Patrick KAREGEYA']],
            ['code' => 'DGE', 'libelle' => 'Genre et Développement Humain', 'commissaire' => ['email' => 'commissaire.dge@ceeac.org', 'name' => 'Yvette NGANDU']],
            ['code' => 'DPSIH', 'libelle' => 'Paix, Sécurité et Stabilité', 'commissaire' => ['email' => 'commissaire.dpsih@ceeac.org', 'name' => 'Mohamed-Saleh DAGACHE']],
            ['code' => 'DEDD', 'libelle' => 'Environnement, Ressources Naturelles, Agriculture et Développement Rural', 'commissaire' => ['email' => 'commissaire.dedd@ceeac.org', 'name' => 'Honoré TABUNA']],
            ['code' => 'DIEM', 'libelle' => 'Infrastructures, Énergie et Marché Commun', 'commissaire' => ['email' => 'commissaire.diem@ceeac.org', 'name' => 'Charles Y. EKOUMOU']],
        ];

        $departementModels = [];
        foreach ($departements as $i => $d) {
            $commissaire = $this->creerUtilisateur(
                $d['commissaire']['email'],
                $d['commissaire']['name'],
                'Commissaire ' . $d['code'],
                'COMM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'commissaire',
            );

            $dep = Departement::firstOrCreate(
                ['code' => $d['code']],
                [
                    'libelle' => $d['libelle'],
                    'commissaire_id' => $commissaire->id,
                    'ordre' => $i + 1,
                    'actif' => true,
                ],
            );

            $departementModels[$d['code']] = $dep;
        }

        // === 3. Directions techniques (rattachées aux Départements) ===
        $directionsTechniques = [
            ['code' => 'DC', 'libelle' => 'Direction du Commerce', 'departement' => 'DAEC'],
            ['code' => 'DI-AEC', 'libelle' => 'Direction des Investissements', 'departement' => 'DAEC'],
            ['code' => 'DAGR', 'libelle' => 'Direction de l\'Agriculture', 'departement' => 'DAPSAR'],
            ['code' => 'DSA', 'libelle' => 'Direction de la Sécurité Alimentaire', 'departement' => 'DAPSAR'],
            ['code' => 'DGENRE', 'libelle' => 'Direction du Genre', 'departement' => 'DGE'],
            ['code' => 'DPSI', 'libelle' => 'Direction de la Paix et Sécurité', 'departement' => 'DPSIH'],
            ['code' => 'DENV', 'libelle' => 'Direction de l\'Environnement', 'departement' => 'DEDD'],
            ['code' => 'DINFRA', 'libelle' => 'Direction des Infrastructures', 'departement' => 'DIEM'],
            ['code' => 'DENERG', 'libelle' => 'Direction de l\'Énergie', 'departement' => 'DIEM'],
        ];

        foreach ($directionsTechniques as $i => $d) {
            $directeur = $this->creerUtilisateur(
                'directeur.' . strtolower($d['code']) . '@ceeac.org',
                'Directeur ' . $d['libelle'],
                'Directeur ' . $d['code'],
                'DIR-T-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'directeur_technique',
            );

            $dir = Direction::firstOrCreate(
                ['code' => $d['code']],
                [
                    'libelle' => $d['libelle'],
                    'type' => 'technique',
                    'departement_id' => $departementModels[$d['departement']]->id,
                    'directeur_id' => $directeur->id,
                    'actif' => true,
                ],
            );

            $directeur->update(['direction_id' => $dir->id]);
        }

        // === 4. Directions d'appui et de soutien (Section 2.5.2 du CDC) ===
        $directionsAppui = [
            ['code' => 'DCRP', 'libelle' => 'Direction de la Communication, des Relations Publiques et du Protocole'],
            ['code' => 'DCMR', 'libelle' => 'Direction de la Coopération et de la Mobilisation des Ressources'],
            ['code' => 'DPPB', 'libelle' => 'Direction de la Planification, des Programmes et du Budget'],
            ['code' => 'DRHMG', 'libelle' => 'Direction des Ressources Humaines et des Moyens Généraux'],
            ['code' => 'DSI', 'libelle' => 'Direction des Systèmes d\'Information'],
        ];

        foreach ($directionsAppui as $i => $d) {
            $directeur = $this->creerUtilisateur(
                'directeur.' . strtolower($d['code']) . '@ceeac.org',
                'Directeur ' . $d['libelle'],
                'Directeur ' . $d['code'],
                'DIR-A-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'directeur_appui',
            );

            $dir = Direction::firstOrCreate(
                ['code' => $d['code']],
                [
                    'libelle' => $d['libelle'],
                    'type' => 'appui_soutien',
                    'departement_id' => null,
                    'directeur_id' => $directeur->id,
                    'actif' => true,
                ],
            );

            $directeur->update(['direction_id' => $dir->id]);
        }

        // === 5. Audit / Contrôle ===
        $this->creerUtilisateur('audit.interne@ceeac.org', 'Auditeur Interne', 'Auditeur Interne', 'AUD-001', 'audit_interne');
        $this->creerUtilisateur('controle.financier@ceeac.org', 'Contrôleur Financier', 'Contrôleur Financier Central', 'CFC-001', 'controle_financier');

        // === 6. Admin technique (compte de démonstration / DSI) ===
        $admin = User::firstOrCreate(
            ['email' => 'admin@ceeac.org'],
            [
                'name' => 'Administrateur DSI',
                'matricule' => 'DSI-ADMIN',
                'fonction' => 'Administrateur Technique',
                'password' => Hash::make('Password@2026'),
                'actif' => true,
                'email_verified_at' => now(),
            ],
        );
        $admin->syncRoles(['admin_technique']);
        // Rattacher l'admin à la DSI
        $dsi = Direction::where('code', 'DSI')->first();
        if ($dsi) {
            $admin->update(['direction_id' => $dsi->id]);
        }

        // === 7. Partenaires techniques et financiers ===
        $partenaires = [
            ['code' => 'UE', 'libelle' => 'Union Européenne', 'type' => 'multilateral'],
            ['code' => 'AFD', 'libelle' => 'Agence Française de Développement', 'type' => 'bilateral'],
            ['code' => 'BM', 'libelle' => 'Banque Mondiale', 'type' => 'multilateral'],
            ['code' => 'BAD', 'libelle' => 'Banque Africaine de Développement', 'type' => 'multilateral'],
            ['code' => 'PNUD', 'libelle' => 'Programme des Nations Unies pour le Développement', 'type' => 'multilateral'],
            ['code' => 'GIZ', 'libelle' => 'Coopération allemande GIZ', 'type' => 'bilateral'],
        ];

        foreach ($partenaires as $p) {
            Partenaire::firstOrCreate(['code' => $p['code']], array_merge($p, ['actif' => true]));
        }

        // === 8. Présidence rattachée à une direction symbolique ===
        // (Le Cabinet de la Présidence est traité comme un rattachement administratif optionnel.)
        $president->update(['direction_id' => null]);
        $vp->update(['direction_id' => null]);

        // Rattacher SG à la Direction Planification (rôle de coordination)
        $dppb = Direction::where('code', 'DPPB')->first();
        if ($dppb) {
            $sg->update(['direction_id' => $dppb->id]);
        }
    }

    protected function creerUtilisateur(string $email, string $name, string $fonction, string $matricule, string $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'matricule' => $matricule,
                'fonction' => $fonction,
                'password' => Hash::make('Password@2026'),
                'actif' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }
}
