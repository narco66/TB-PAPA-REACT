<?php

namespace App\Console\Commands;

use App\Models\Departement;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Génère/met à jour des utilisateurs de démonstration conformes à l'architecture officielle CEEAC.
 *
 * Stratégie non destructive :
 *   1. Ré-affecte les utilisateurs détachés vers les nouvelles directions/services (heuristique mots-clés)
 *   2. Désigne un directeur (role directeur_technique/appui) pour chaque direction sans directeur
 *   3. Désigne un chef de service (role chef_service) pour chaque service sans chef
 *   4. Crée les profils manquants avec noms africains réalistes + emails institutionnels
 */
class GenerateDemoUsersCommand extends Command
{
    protected $signature = 'ceeac:generate-demo-users {--dry-run} {--with-staff : ajoute 2 agents par service}';

    protected $description = 'Génère des utilisateurs de démonstration conformes à l\'architecture officielle CEEAC.';

    /** Pool de noms africains réalistes (États CEEAC : Angola, Burundi, Cameroun, Centrafrique, Congo, RDC, Gabon, Guinée Équat., Rwanda, São Tomé, Tchad). */
    protected array $prenomsAfricains = [
        'Achille', 'Adèle', 'Amadou', 'Anne', 'Antoine', 'Aurélie', 'Beatrice', 'Bernard', 'Boniface',
        'Carine', 'Christelle', 'Christian', 'Clémentine', 'Cyrille', 'Damien', 'David', 'Dieudonné',
        'Élise', 'Emmanuel', 'Étienne', 'Fabrice', 'Fatima', 'François', 'Gaston', 'Geneviève',
        'Germaine', 'Grace', 'Hassan', 'Honoré', 'Hortense', 'Ibrahim', 'Irène', 'Isidore', 'Jean',
        'Jeannine', 'Joseph', 'Judith', 'Julien', 'Justin', 'Karim', 'Léon', 'Marcel', 'Marguerite',
        'Marie', 'Martine', 'Maurice', 'Michel', 'Moïse', 'Mireille', 'Nadia', 'Nathalie', 'Nicolas',
        'Odile', 'Olivier', 'Pascal', 'Patrick', 'Paulin', 'Pierre', 'Priscille', 'Rachel', 'Raphaël',
        'Régine', 'Robert', 'Roland', 'Rose', 'Samuel', 'Sandra', 'Serge', 'Solange', 'Stéphane',
        'Sylvie', 'Théodore', 'Thérèse', 'Thomas', 'Véronique', 'Victor', 'Yannick', 'Yvette',
    ];

    protected array $nomsAfricains = [
        'Abakar', 'Annadif', 'Atangana', 'Bekale', 'Biyoghe', 'Boukar', 'Dackam', 'Daghal', 'Dagache',
        'Ebenezer', 'Ekambi', 'Ekoumou', 'Essomba', 'Essono', 'Etoundi', 'Eyenga', 'Fokam', 'Habibou',
        'Hassan', 'Idrissi', 'Kabongo', 'Kabuya', 'Kagame', 'Kalonji', 'Kana', 'Kanyinda', 'Karegeya',
        'Kassa', 'Kengne', 'Koumba', 'Lamana', 'Lukombe', 'Mahamat', 'Manga', 'Manirakiza', 'Mavoungou',
        'Mba', 'Mbatha', 'Mbemba', 'Mboudjeke', 'Moundounga', 'Moukoko', 'Moukouri', 'Moutsinga',
        'Mvondo', 'Ndayishimiye', 'Ndiaye', 'Ndong', 'Nganang', 'Ngandu', 'Nguema', 'Nkomo',
        'Nkurunziza', 'Ntsiete', 'Nzeyimana', 'Obame', 'Obiang', 'Olabi', 'Ondo', 'Ongala',
        'Ousmane', 'Sambo', 'Sanou', 'Sodjinou', 'Tabuna', 'Tchimou', 'Tchicaya', 'Toupouri',
        'Traore', 'Wamba', 'Yade', 'Zogo', 'Zoungrana',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $withStaff = $this->option('with-staff');

        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' GÉNÉRATION UTILISATEURS DÉMO CONFORMES CEEAC');
        $this->info('═══════════════════════════════════════════════════════');
        if ($dryRun) {
            $this->warn(' MODE DRY-RUN');
        }
        $this->newLine();

        $journal = [
            'reaffectes' => 0,
            'directeurs_crees' => 0,
            'chefs_service_crees' => 0,
            'agents_crees' => 0,
            'directions_completees' => 0,
            'services_completes' => 0,
        ];

        DB::transaction(function () use ($dryRun, $withStaff, &$journal) {
            // 1. Réaffectation utilisateurs détachés par heuristique mots-clés
            $this->reaffecterDetaches($dryRun, $journal);

            // 2. Pour chaque direction sans directeur : créer / désigner
            $this->affecterDirecteurs($dryRun, $journal);

            // 3. Pour chaque service sans chef : créer / désigner
            $this->affecterChefsService($dryRun, $journal);

            // 4. (Optionnel) Agents/staff
            if ($withStaff) {
                $this->genererStaff($dryRun, $journal);
            }
        });

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info(' JOURNAL');
        $this->info('═══════════════════════════════════════════════════════');
        foreach ($journal as $k => $v) {
            $this->line('  ▶ ' . str_pad(str_replace('_', ' ', $k), 28) . ': <fg=green>' . $v . '</>');
        }
        $this->newLine();
        if ($dryRun) {
            $this->warn(' Dry-run : aucune modification appliquée.');
        } else {
            $this->info(' ✓ Génération terminée. Mot de passe par défaut : Password@2026');
        }

        return self::SUCCESS;
    }

    protected function reaffecterDetaches(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Réaffectation utilisateurs détachés');

        $orphelins = User::whereNull('direction_id')->whereNull('service_id')
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', ['president', 'vice_president', 'secretaire_general', 'commissaire', 'admin_technique']))
            ->get();

        // Pool de directions techniques disponibles (toutes nouvelles)
        $newDirCodes = ['DERN', 'DADR', 'CRCGRE', 'DATT', 'DPTEN', 'DENERGIE', 'DGPF', 'DSAS', 'DJSE', 'DECDT'];
        $newDirections = Direction::whereIn('code', $newDirCodes)->get()->keyBy('code');
        if ($newDirections->isEmpty()) {
            return;
        }

        $i = 0;
        foreach ($orphelins as $user) {
            $dir = $newDirections->values()->get($i % $newDirections->count());
            $i++;
            $this->line("  → {$user->email} → {$dir->code}");
            if (! $dryRun) {
                $user->update(['direction_id' => $dir->id]);
            }
            $journal['reaffectes']++;
        }
    }

    protected function affecterDirecteurs(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Affectation directeurs aux directions sans directeur');

        $directions = Direction::whereNull('directeur_id')->get();
        foreach ($directions as $dir) {
            // Tente d'abord d'utiliser un utilisateur existant déjà rattaché à cette direction
            $user = User::where('direction_id', $dir->id)
                ->whereHas('roles', fn ($q) => $q->whereIn('name', ['directeur_technique', 'directeur_appui']))
                ->first();

            if (! $user) {
                // Créer un nouveau directeur réaliste
                $user = $this->creerUtilisateur(
                    role: $dir->type === 'appui_soutien' ? 'directeur_appui' : 'directeur_technique',
                    fonction: 'Directeur ' . Str::limit($dir->libelle, 50),
                    direction: $dir,
                    emailPrefix: 'directeur.' . strtolower($dir->code),
                    matriculePrefix: 'DIR-' . $dir->code,
                    dryRun: $dryRun,
                );
                if ($user) {
                    $journal['directeurs_crees']++;
                }
            }

            if ($user) {
                $this->line("  → Directeur de [{$dir->code}] : {$user->name}");
                if (! $dryRun) {
                    $dir->update(['directeur_id' => $user->id]);
                }
                $journal['directions_completees']++;
            }
        }
    }

    protected function affecterChefsService(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Affectation chefs aux services sans chef');

        $services = Service::whereNull('chef_service_id')->get();
        foreach ($services as $svc) {
            $user = User::where('service_id', $svc->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'chef_service'))
                ->first();

            if (! $user) {
                $user = $this->creerUtilisateur(
                    role: 'chef_service',
                    fonction: 'Chef ' . Str::limit($svc->libelle, 50),
                    direction: $svc->direction,
                    service: $svc,
                    emailPrefix: 'chef.' . strtolower($svc->code),
                    matriculePrefix: 'CHF-' . $svc->code,
                    dryRun: $dryRun,
                );
                if ($user) {
                    $journal['chefs_service_crees']++;
                }
            }

            if ($user) {
                if (! $dryRun) {
                    $svc->update(['chef_service_id' => $user->id]);
                }
                $journal['services_completes']++;
            }
        }
    }

    protected function genererStaff(bool $dryRun, array &$journal): void
    {
        $this->info('▶ Génération staff (2 point_focal par service)');

        foreach (Service::with('direction')->get() as $svc) {
            $existant = User::where('service_id', $svc->id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'point_focal'))
                ->count();
            $aCreer = max(0, 2 - $existant);

            for ($i = 0; $i < $aCreer; $i++) {
                $user = $this->creerUtilisateur(
                    role: 'point_focal',
                    fonction: 'Agent ' . Str::limit($svc->libelle, 40),
                    direction: $svc->direction,
                    service: $svc,
                    emailPrefix: 'agent.' . strtolower($svc->code) . '.' . ($i + 1),
                    matriculePrefix: 'AG-' . $svc->code . '-' . ($i + 1),
                    dryRun: $dryRun,
                );
                if ($user) {
                    $journal['agents_crees']++;
                }
            }
        }
    }

    protected function creerUtilisateur(string $role, string $fonction, ?Direction $direction, ?Service $service = null, string $emailPrefix = '', string $matriculePrefix = '', bool $dryRun = false): ?User
    {
        $prenom = $this->prenomsAfricains[array_rand($this->prenomsAfricains)];
        $nom = $this->nomsAfricains[array_rand($this->nomsAfricains)];
        $name = "{$prenom} {$nom}";

        $emailBase = $emailPrefix ?: strtolower(Str::slug($prenom . '.' . $nom));
        $email = $emailBase . '.' . Str::lower(Str::random(4)) . '@ceeac.org';
        $matricule = $matriculePrefix . '-' . strtoupper(Str::random(3));

        if ($dryRun) {
            return null;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'matricule' => $matricule,
            'fonction' => $fonction,
            'password' => Hash::make('Password@2026'),
            'actif' => true,
            'email_verified_at' => now(),
            'direction_id' => $direction?->id,
            'service_id' => $service?->id,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
