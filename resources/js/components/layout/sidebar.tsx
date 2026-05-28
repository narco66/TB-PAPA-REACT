import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    Boxes,
    Building2,
    ChevronDown,
    ClipboardCheck,
    ClipboardList,
    Coins,
    Cog,
    FileBarChart,
    FileText,
    GanttChartSquare,
    KeyRound,
    Layers,
    LayoutDashboard,
    LifeBuoy,
    ListChecks,
    Network,
    Package,
    PiggyBank,
    Receipt,
    ShieldCheck,
    Target,
    Upload,
    Users,
    Wallet,
} from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface NavItem {
    title: string;
    href: string;
    icon: typeof LayoutDashboard;
    permission?: string;
}

interface NavSection {
    label: string;
    icon?: typeof LayoutDashboard;
    items: NavItem[];
    /** Si true, la section ne peut pas être repliée (ex: Accueil) */
    pinned?: boolean;
}

function useNavigation(): NavSection[] {
    const { auth } = usePage<PageProps>().props;
    const perms = useMemo(() => new Set(auth.user?.permissions ?? []), [auth.user]);
    const has = (p?: string) => !p || perms.has(p);

    return [
        // 1. ACCUEIL — toujours visible, jamais repliable (cycle commence ici)
        {
            label: 'Accueil',
            icon: LayoutDashboard,
            pinned: true,
            items: ([
                { title: 'Tableau de bord', href: '/dashboard', icon: LayoutDashboard },
            ] as NavItem[]).filter((i) => has(i.permission)),
        },

        // 2. PLANIFIER — Cadre stratégique RBM/GAR (descendant)
        {
            label: 'Pilotage stratégique',
            icon: ClipboardList,
            items: [
                { title: 'PAPA — Plan Annuel', href: '/papa', icon: ClipboardList, permission: 'papa.viewAny' },
                { title: 'Axes stratégiques', href: '/rbm/axes', icon: Target, permission: 'view_axes' },
                { title: 'Produits', href: '/rbm/produits', icon: Package, permission: 'view_produits' },
                { title: 'Sous-Produits', href: '/rbm/sous-produits', icon: Boxes, permission: 'view_sous_produits' },
            ].filter((i) => has(i.permission)),
        },

        // 3. EXÉCUTER — Mise en œuvre opérationnelle
        {
            label: 'Exécution opérationnelle',
            icon: GanttChartSquare,
            items: [
                { title: 'Activités & Gantt', href: '/activites', icon: GanttChartSquare, permission: 'view_activites' },
                { title: 'Tâches', href: '/rbm/taches', icon: ListChecks, permission: 'view_taches' },
            ].filter((i) => has(i.permission)),
        },

        // 4. MESURER — Performance et anomalies
        {
            label: 'Performance & Alertes',
            icon: BarChart3,
            items: [
                { title: 'Indicateurs (KPI)', href: '/indicateurs', icon: BarChart3, permission: 'indicateur.viewAny' },
                { title: 'Alertes & Risques', href: '/alertes', icon: AlertTriangle, permission: 'alerte.viewAny' },
            ].filter((i) => has(i.permission)),
        },

        // 5. FINANCER — Cadre budgétaire annuel
        {
            label: 'Budget institutionnel',
            icon: Wallet,
            items: [
                { title: 'Tableau de bord budget', href: '/budget', icon: BarChart3, permission: 'view_budget_dashboard' },
                { title: 'Exercices budgétaires', href: '/budget/exercices', icon: Wallet, permission: 'view_budget' },
                { title: 'Lignes budgétaires', href: '/budget/lignes', icon: PiggyBank, permission: 'view_budget' },
                { title: 'Import / Export', href: '/budget/imports', icon: Upload, permission: 'import_budget' },
            ].filter((i) => has(i.permission)),
        },

        // 6. DÉPENSER — Cycle complet de la dépense publique (RGCP/IPSAS)
        {
            label: 'Chaîne de la dépense',
            icon: Coins,
            items: [
                { title: 'Tableau de bord dépense', href: '/expense', icon: BarChart3, permission: 'expense.viewAny' },
                { title: 'Expressions du besoin', href: '/expense/requests', icon: Receipt, permission: 'expense.viewAny' },
                { title: 'Service fait & Réception', href: '/expense/service-fait', icon: ClipboardCheck, permission: 'expense.viewAny' },
                { title: 'Fournisseurs', href: '/expense/suppliers', icon: Building2, permission: 'supplier.viewAny' },
            ].filter((i) => has(i.permission)),
        },

        // 7. CONTRÔLER — Audit interne IGS (IIA / IFACI / ISO 19011 / COSO)
        {
            label: 'Audit interne IGS',
            icon: ShieldCheck,
            items: [
                { title: 'Tableau de bord audit', href: '/audit', icon: ShieldCheck, permission: 'audit_interne.view' },
                { title: "Plans d'audit", href: '/audit/plans', icon: ClipboardCheck, permission: 'audit_interne.view' },
                { title: "Missions d'audit", href: '/audit/missions', icon: Target, permission: 'audit_interne.view' },
                { title: 'Recommandations', href: '/audit/recommandations', icon: ListChecks, permission: 'audit_interne.view' },
            ].filter((i) => has(i.permission)),
        },

        // 8. RESTITUER — Documents et rapports institutionnels
        {
            label: 'Documents & Reporting',
            icon: FileText,
            items: [
                { title: 'Documents (GED)', href: '/documents', icon: FileText, permission: 'document.viewAny' },
                { title: 'Rapports PDF', href: '/rapports', icon: FileBarChart, permission: 'generate_reports' },
            ].filter((i) => has(i.permission)),
        },

        // 9. ADMINISTRER — Hiérarchie institutionnelle CEEAC (vue métier)
        {
            label: 'Organisation institutionnelle',
            icon: Network,
            items: [
                { title: 'Organigramme', href: '/admin/organigramme', icon: Network, permission: 'departement.manage' },
                { title: 'Départements', href: '/admin/departements', icon: Building2, permission: 'departement.manage' },
                { title: 'Directions', href: '/admin/directions', icon: Boxes, permission: 'direction.manage' },
                { title: 'Services', href: '/admin/services', icon: Layers, permission: 'service.viewAny' },
            ].filter((i) => has(i.permission)),
        },

        // 10. PARAMÉTRER — Administration système et sécurité (technique)
        {
            label: 'Administration système',
            icon: Cog,
            items: [
                { title: 'Utilisateurs', href: '/admin/users', icon: Users, permission: 'user.viewAny' },
                { title: 'Rôles', href: '/admin/roles', icon: ShieldCheck, permission: 'role.manage' },
                { title: 'Permissions', href: '/admin/permissions', icon: KeyRound, permission: 'permission.manage' },
                { title: "Journal d'audit système", href: '/admin/audit', icon: ShieldCheck, permission: 'audit.viewLog' },
            ].filter((i) => has(i.permission)),
        },
    ].filter((s) => s.items.length > 0);
}

const STORAGE_KEY = 'tbpapa.sidebar.collapsed';

function useCollapsedSections(): [Set<string>, (label: string) => void] {
    const [collapsed, setCollapsed] = useState<Set<string>>(() => {
        if (typeof window === 'undefined') return new Set();
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return new Set(raw ? JSON.parse(raw) : []);
        } catch {
            return new Set();
        }
    });

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify([...collapsed]));
        } catch {
            /* localStorage unavailable */
        }
    }, [collapsed]);

    const toggle = (label: string) => {
        setCollapsed((prev) => {
            const next = new Set(prev);
            if (next.has(label)) next.delete(label);
            else next.add(label);
            return next;
        });
    };

    return [collapsed, toggle];
}

export function Sidebar() {
    const sections = useNavigation();
    const { url } = usePage();
    const [collapsed, toggleCollapsed] = useCollapsedSections();

    return (
        <aside className="hidden border-r bg-card md:flex md:w-64 md:shrink-0 md:flex-col">
            {/* Marque */}
            <Link href="/dashboard" className="flex h-16 items-center gap-3 border-b px-5 hover:bg-accent/40 transition-colors">
                <img src="/images/LOGO-CEEAC.jpg" alt="CEEAC" className="h-10 w-10 rounded-lg bg-white p-0.5 object-contain ring-1 ring-border" />
                <div className="flex flex-col leading-tight">
                    <span className="font-bold text-sm text-foreground">TB-PAPA</span>
                    <span className="text-[10px] text-muted-foreground uppercase tracking-wider">CEEAC · ECCAS</span>
                </div>
            </Link>

            {/* Navigation */}
            <nav className="flex-1 flex flex-col gap-1 px-2 py-4 overflow-y-auto">
                {sections.map((section) => {
                    const isCollapsed = !section.pinned && collapsed.has(section.label);
                    const hasActiveItem = section.items.some((i) => url === i.href || url.startsWith(i.href + '/'));
                    const SectionIcon = section.icon;

                    return (
                        <div key={section.label} className="mb-1">
                            {section.pinned ? (
                                // Section pinned : pas de header, items direct
                                <ul className="flex flex-col gap-0.5">
                                    {section.items.map((item) => (
                                        <NavLink key={item.href} item={item} url={url} />
                                    ))}
                                </ul>
                            ) : (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => toggleCollapsed(section.label)}
                                        className={cn(
                                            'group w-full flex items-center gap-2 px-3 py-1.5 rounded-md text-[11px] font-bold uppercase tracking-wider transition-colors',
                                            hasActiveItem
                                                ? 'text-ceeac-blue'
                                                : 'text-muted-foreground hover:text-foreground hover:bg-accent/50',
                                        )}
                                        aria-expanded={!isCollapsed}
                                    >
                                        {SectionIcon && <SectionIcon className="h-3.5 w-3.5 shrink-0" />}
                                        <span className="flex-1 text-left">{section.label}</span>
                                        <ChevronDown
                                            className={cn(
                                                'h-3 w-3 transition-transform shrink-0',
                                                isCollapsed ? '-rotate-90' : '',
                                            )}
                                        />
                                    </button>
                                    {!isCollapsed && (
                                        <ul className="mt-1 flex flex-col gap-0.5">
                                            {section.items.map((item) => (
                                                <NavLink key={item.href} item={item} url={url} />
                                            ))}
                                        </ul>
                                    )}
                                </>
                            )}
                        </div>
                    );
                })}
            </nav>

            {/* Footer compact */}
            <div className="border-t px-4 py-3 text-[10px] text-muted-foreground">
                <div className="flex items-center justify-between mb-1">
                    <span className="font-semibold">TB-PAPA v2.0</span>
                    <a href="https://ceeac-eccas.org" target="_blank" rel="noopener" className="flex items-center gap-1 hover:text-foreground transition-colors">
                        <LifeBuoy className="h-3 w-3" />
                        Aide
                    </a>
                </div>
                <p className="opacity-70">© {new Date().getFullYear()} CEEAC · ECCAS</p>
            </div>
        </aside>
    );
}

function NavLink({ item, url }: { item: NavItem; url: string }) {
    const Icon = item.icon;
    const active = url === item.href || url.startsWith(item.href + '/');

    return (
        <li>
            <Link
                href={item.href}
                className={cn(
                    'group relative flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-all',
                    active
                        ? 'bg-ceeac-blue/10 text-ceeac-blue font-medium'
                        : 'text-foreground/70 hover:bg-accent hover:text-foreground',
                )}
            >
                {active && (
                    <span className="absolute left-0 top-1.5 bottom-1.5 w-0.5 rounded-r bg-ceeac-blue" />
                )}
                <Icon className={cn('h-4 w-4 shrink-0', active ? 'text-ceeac-blue' : '')} />
                <span className="flex-1 truncate">{item.title}</span>
            </Link>
        </li>
    );
}
