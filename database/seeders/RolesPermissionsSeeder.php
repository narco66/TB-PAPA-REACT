<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Rôles institutionnels conformes au CDC Section 4 (RACI) et Section 7.2.1.
     */
    public const ROLES = [
        'president' => 'Président de la Commission',
        'vice_president' => 'Vice-Président de la Commission',
        'commissaire' => 'Commissaire (Chef de Département technique)',
        'secretaire_general' => 'Secrétaire Général',
        'directeur_technique' => 'Directeur technique',
        'directeur_appui' => 'Directeur d\'appui et de soutien',
        'chef_service' => 'Chef de service',
        'point_focal' => 'Point focal opérationnel',
        'audit_interne' => 'Auditeur interne',
        'controle_financier' => 'Contrôle financier',
        'partenaire' => 'Partenaire technique et financier',
        'admin_fonctionnel' => 'Administrateur fonctionnel',
        'admin_technique' => 'Administrateur technique',
    ];

    /** Niveaux RBM CEEAC (chaîne officielle). */
    public const RBM_RESSOURCES = ['axes', 'produits', 'sous_produits', 'activites', 'taches'];

    public const RBM_ACTIONS = ['view', 'create', 'edit', 'delete', 'validate'];

    /** Permissions Budget institutionnel + cycle IPSAS (14 permissions dédiées). */
    public const BUDGET_PERMISSIONS = [
        'view_budget', 'create_budget', 'edit_budget', 'delete_budget',
        'import_budget', 'export_budget', 'validate_budget', 'control_budget',
        'archive_budget', 'view_budget_dashboard',
        // Cycle IPSAS : Engagement → Liquidation → Ordonnancement → Paiement
        'engager_budget', 'liquider_budget', 'ordonnancer_budget', 'payer_budget',
    ];

    /** Permissions Reporting PDF institutionnel (8 permissions). */
    public const REPORTING_PERMISSIONS = [
        'generate_reports', 'export_reports', 'validate_reports', 'archive_reports',
        'download_reports', 'manage_templates', 'manage_report_settings', 'schedule_reports',
    ];

    public static function permissions(): array
    {
        $permissions = [
            // PAPA (référentiel annuel racine)
            'papa.viewAny', 'papa.view', 'papa.create', 'papa.update', 'papa.delete',
            'papa.submit', 'papa.validate', 'papa.close', 'papa.archive', 'papa.import', 'papa.revise',
            // Indicateurs (rattachés au niveau Sous-Produit)
            'indicateur.viewAny', 'indicateur.view', 'indicateur.create', 'indicateur.update', 'indicateur.delete',
            'indicateur.saisie', 'indicateur.validate',
            // Budgets
            'budget.viewAny', 'budget.view', 'budget.create', 'budget.update', 'budget.delete',
            'budget.engage', 'budget.consomme', 'budget.validate',
            // GED
            'document.viewAny', 'document.view', 'document.upload', 'document.update', 'document.delete',
            'document.validate', 'document.viewConfidential',
            // Alertes
            'alerte.viewAny', 'alerte.view', 'alerte.assign', 'alerte.resolve',
            // Reporting
            'rapport.executif', 'rapport.sectoriel', 'rapport.operationnel',
            'rapport.budgetaire', 'rapport.audit', 'rapport.export',
            // Tableaux de bord
            'dashboard.presidence', 'dashboard.commissaire', 'dashboard.sg',
            'dashboard.direction', 'dashboard.audit',
            // Administration
            'user.viewAny', 'user.view', 'user.create', 'user.update', 'user.delete',
            'role.manage', 'permission.manage',
            'departement.manage', 'direction.manage', 'service.viewAny', 'service.manage', 'partenaire.manage',
            'audit.viewLog',
            // Module Audit interne IGS (IIA / IFACI)
            'audit_interne.view', 'audit_interne.plan_create', 'audit_interne.plan_validate',
            'audit_interne.mission_create', 'audit_interne.mission_execute', 'audit_interne.mission_close',
            'audit_interne.constat_create', 'audit_interne.recommandation_create',
            'audit_interne.suivi_create',
            // Chaîne de la dépense (Phase 1)
            'expense.viewAny', 'expense.view', 'expense.create', 'expense.update',
            'expense.submit', 'expense.validate_hierarchique', 'expense.reject',
            'expense.engage', 'expense.cancel',
            'supplier.viewAny', 'supplier.manage',
            'system.manage',
        ];

        // 25 permissions RBM (5 niveaux × 5 actions)
        foreach (self::RBM_RESSOURCES as $res) {
            foreach (self::RBM_ACTIONS as $act) {
                $permissions[] = "{$act}_{$res}";
            }
        }

        // 10 permissions Budget institutionnel
        $permissions = array_merge($permissions, self::BUDGET_PERMISSIONS);

        // 8 permissions Reporting PDF
        $permissions = array_merge($permissions, self::REPORTING_PERMISSIONS);

        return $permissions;
    }

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::permissions() as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        foreach (self::ROLES as $name => $libelle) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $this->attribuerPermissionsParRole();
    }

    /**
     * Distribution des permissions par rôle conformément à la matrice RACI (Section 4.4).
     */
    protected function attribuerPermissionsParRole(): void
    {
        // === Permissions RBM par profil ===
        $rbmAll = $this->rbmPerms(self::RBM_ACTIONS);
        $rbmRead = $this->rbmPerms(['view']);
        $rbmReadValidate = $this->rbmPerms(['view', 'validate']);
        $rbmReadWrite = $this->rbmPerms(['view', 'create', 'edit', 'validate']);
        $rbmFull = $this->rbmPerms(['view', 'create', 'edit', 'delete']);

        // === Président : vision globale, validation finale du PAPA, lecture RBM + validation Axes/Produits ===
        Role::findByName('president')->syncPermissions([
            'papa.viewAny', 'papa.view', 'papa.validate', 'papa.archive',
            ...$rbmRead,
            'validate_axes', 'validate_produits',
            'indicateur.viewAny', 'indicateur.view',
            'budget.viewAny', 'budget.view',
            // Budget institutionnel
            'view_budget', 'validate_budget', 'archive_budget', 'view_budget_dashboard', 'export_budget',
            'document.viewAny', 'document.view', 'document.viewConfidential',
            'alerte.viewAny', 'alerte.view',
            'rapport.executif', 'rapport.sectoriel', 'rapport.budgetaire', 'rapport.export',
            'dashboard.presidence',
            // Reporting PDF institutionnel
            'generate_reports', 'export_reports', 'validate_reports', 'download_reports',
        ]);

        Role::findByName('vice_president')->syncPermissions(
            Role::findByName('president')->permissions->pluck('name')->toArray(),
        );

        // === Commissaire : pilotage sectoriel — création/édition Axes/Produits + validation ===
        Role::findByName('commissaire')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$this->rbmPerms(['view', 'create', 'edit', 'validate'], ['axes', 'produits', 'sous_produits']),
            ...$this->rbmPerms(['view', 'edit'], ['activites', 'taches']),
            'indicateur.viewAny', 'indicateur.view', 'indicateur.create', 'indicateur.update', 'indicateur.validate',
            'budget.viewAny', 'budget.view', 'budget.validate',
            // Budget institutionnel — pilotage sectoriel + ordonnateur IPSAS
            'view_budget', 'validate_budget', 'control_budget', 'view_budget_dashboard', 'export_budget',
            'engager_budget', 'ordonnancer_budget',
            'document.viewAny', 'document.view', 'document.upload', 'document.validate', 'document.viewConfidential',
            'alerte.viewAny', 'alerte.view', 'alerte.assign', 'alerte.resolve',
            'rapport.executif', 'rapport.sectoriel', 'rapport.operationnel', 'rapport.budgetaire', 'rapport.export',
            'dashboard.commissaire',
            // Reporting PDF
            'generate_reports', 'export_reports', 'validate_reports', 'download_reports',
        ]);

        // === Secrétaire Général : coordination + import + lecture ===
        Role::findByName('secretaire_general')->syncPermissions([
            'papa.viewAny', 'papa.view', 'papa.import', 'papa.submit', 'papa.revise', 'papa.close',
            ...$rbmRead,
            'indicateur.viewAny', 'indicateur.view',
            'budget.viewAny', 'budget.view',
            // Budget institutionnel — coordination, import et contrôle
            'view_budget', 'create_budget', 'edit_budget', 'import_budget', 'export_budget',
            'control_budget', 'view_budget_dashboard',
            'document.viewAny', 'document.view', 'document.upload',
            'alerte.viewAny', 'alerte.view', 'alerte.assign',
            'rapport.executif', 'rapport.sectoriel', 'rapport.operationnel', 'rapport.budgetaire', 'rapport.export',
            'dashboard.sg',
            // Reporting PDF — coordination, archivage, planification
            'generate_reports', 'export_reports', 'archive_reports', 'download_reports', 'schedule_reports',
        ]);

        // === Directeur technique : exécution sectorielle (Sous-Produits / Activités / Tâches) ===
        Role::findByName('directeur_technique')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$this->rbmPerms(['view'], ['axes', 'produits']),
            ...$this->rbmPerms(['view', 'create', 'edit', 'delete'], ['sous_produits', 'activites', 'taches']),
            'indicateur.viewAny', 'indicateur.view', 'indicateur.create', 'indicateur.update', 'indicateur.saisie',
            'budget.viewAny', 'budget.view', 'budget.create', 'budget.update', 'budget.engage', 'budget.consomme',
            // Budget institutionnel — lecture + export + cycle IPSAS (ordonnateur)
            'view_budget', 'view_budget_dashboard', 'export_budget',
            'engager_budget', 'ordonnancer_budget',
            'document.viewAny', 'document.view', 'document.upload', 'document.update',
            'alerte.viewAny', 'alerte.view', 'alerte.resolve',
            'rapport.operationnel', 'rapport.budgetaire', 'rapport.export',
            'dashboard.direction',
            // Reporting PDF — génération opérationnelle
            'generate_reports', 'export_reports', 'download_reports',
        ]);

        Role::findByName('directeur_appui')->syncPermissions(
            Role::findByName('directeur_technique')->permissions->pluck('name')->toArray(),
        );

        // === Chef de service : lecture RBM + édition Activités/Tâches ===
        Role::findByName('chef_service')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$rbmRead,
            ...$this->rbmPerms(['edit'], ['activites', 'taches']),
            'indicateur.viewAny', 'indicateur.view', 'indicateur.saisie',
            'budget.viewAny', 'budget.view',
            'document.viewAny', 'document.view', 'document.upload',
            'alerte.viewAny', 'alerte.view',
            'rapport.operationnel',
        ]);

        // === Point focal : exécution opérationnelle (Tâches uniquement) ===
        Role::findByName('point_focal')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$rbmRead,
            'edit_taches',
            'indicateur.viewAny', 'indicateur.view', 'indicateur.saisie',
            'budget.viewAny', 'budget.view',
            'document.viewAny', 'document.view', 'document.upload',
            'alerte.viewAny', 'alerte.view',
            'rapport.operationnel',
        ]);

        // === Audit / Contrôle : lecture totale + audit log + contrôle budgétaire ===
        Role::findByName('audit_interne')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$rbmRead,
            'indicateur.viewAny', 'indicateur.view',
            'budget.viewAny', 'budget.view',
            // Budget institutionnel — audit + comptable assignataire IPSAS (paiement)
            'view_budget', 'control_budget', 'view_budget_dashboard', 'export_budget',
            'liquider_budget', 'payer_budget',
            'document.viewAny', 'document.view', 'document.viewConfidential',
            'alerte.viewAny', 'alerte.view',
            'rapport.audit', 'rapport.executif', 'rapport.sectoriel', 'rapport.budgetaire', 'rapport.export',
            'audit.viewLog',
            'dashboard.audit',
            // Reporting PDF — audit complet et téléchargement
            'generate_reports', 'export_reports', 'download_reports', 'archive_reports',
            // Module Audit interne IGS — accès complet
            'audit_interne.view', 'audit_interne.plan_create', 'audit_interne.plan_validate',
            'audit_interne.mission_create', 'audit_interne.mission_execute', 'audit_interne.mission_close',
            'audit_interne.constat_create', 'audit_interne.recommandation_create',
            'audit_interne.suivi_create',
        ]);

        Role::findByName('controle_financier')->syncPermissions(
            Role::findByName('audit_interne')->permissions->pluck('name')->toArray(),
        );

        // === Partenaire : accès très restreint ===
        Role::findByName('partenaire')->syncPermissions([
            'papa.viewAny', 'papa.view',
            ...$rbmRead,
            'indicateur.viewAny', 'indicateur.view',
            'budget.viewAny', 'budget.view',
            'rapport.executif', 'rapport.export',
        ]);

        // === Admin fonctionnel ===
        Role::findByName('admin_fonctionnel')->syncPermissions([
            'user.viewAny', 'user.view', 'user.create', 'user.update',
            'role.manage', 'permission.manage', 'departement.manage', 'direction.manage', 'service.viewAny', 'service.manage', 'partenaire.manage',
            'papa.viewAny', 'papa.view',
            ...$rbmRead,
            'audit.viewLog',
        ]);

        // === Chaîne de la dépense — assignation par rôle (APRÈS tous les syncPermissions pour éviter écrasement) ===
        // Lecture transversale : tous les rôles métier voient le module
        foreach (['president', 'vice_president', 'secretaire_general', 'commissaire',
            'directeur_technique', 'directeur_appui', 'chef_service', 'point_focal',
            'controle_financier', 'audit_interne'] as $role) {
            Role::findByName($role)->givePermissionTo([
                'expense.viewAny', 'expense.view', 'supplier.viewAny',
            ]);
        }
        // Initiateurs : créer / soumettre / annuler
        foreach (['point_focal', 'chef_service', 'directeur_technique', 'directeur_appui'] as $role) {
            Role::findByName($role)->givePermissionTo([
                'expense.create', 'expense.update', 'expense.submit', 'expense.cancel',
            ]);
        }
        // Responsables hiérarchiques + ordonnateurs : validation + engagement + gestion fournisseurs
        foreach (['commissaire', 'secretaire_general', 'directeur_technique', 'directeur_appui'] as $role) {
            Role::findByName($role)->givePermissionTo([
                'expense.validate_hierarchique', 'expense.reject', 'expense.engage', 'supplier.manage',
            ]);
        }
        // Contrôle financier : ajoute la validation hiérarchique (visa)
        Role::findByName('controle_financier')->givePermissionTo([
            'expense.validate_hierarchique',
        ]);

        // === Admin technique : toutes permissions ===
        Role::findByName('admin_technique')->givePermissionTo(Permission::all());
    }

    /** Génère la liste des permissions RBM pour un sous-ensemble d'actions/ressources. */
    protected function rbmPerms(array $actions, ?array $ressources = null): array
    {
        $ressources = $ressources ?? self::RBM_RESSOURCES;
        $result = [];
        foreach ($ressources as $res) {
            foreach ($actions as $act) {
                $result[] = "{$act}_{$res}";
            }
        }

        return $result;
    }
}
