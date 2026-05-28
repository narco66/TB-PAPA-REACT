import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    CheckCircle2,
    ClipboardCheck,
    FileBarChart,
    GanttChartSquare,
    Globe2,
    Layers,
    LogIn,
    PiggyBank,
    Sparkles,
    ShieldCheck,
    Target,
    Wallet,
    Zap,
} from 'lucide-react';
import { Button } from '@/components/ui/button';

const modules = [
    { icon: ClipboardCheck, title: "PAPA — Plan d'Action Prioritaire", description: 'Référentiel annuel racine, soumission, validation et archivage du plan stratégique CEEAC.', gradient: 'from-blue-500 to-indigo-600', href: '/modules/papa' },
    { icon: Target, title: 'Chaîne RBM/GAR', description: 'Axe → Produit → Sous-Produit → Activité → Tâche, principes de Gestion Axée sur les Résultats.', gradient: 'from-emerald-500 to-teal-600', href: '/modules/rbm' },
    { icon: GanttChartSquare, title: 'Activités & Gantt', description: 'Planification opérationnelle, suivi d\'avancement, vue Gantt avec filtres période.', gradient: 'from-violet-500 to-purple-600', href: '/modules/gantt' },
    { icon: BarChart3, title: 'Indicateurs CMR', description: 'Typologie OCDE, désagrégation, paliers trimestriels, mesure fine de la performance.', gradient: 'from-amber-500 to-orange-600', href: '/modules/indicateurs' },
    { icon: Wallet, title: 'Budget IPSAS', description: 'Exercices, lignes CEEAC-EM/PTF, cycle engagement → liquidation → ordonnancement → paiement.', gradient: 'from-rose-500 to-pink-600', href: '/modules/budget' },
    { icon: ShieldCheck, title: 'Audit interne IGS', description: "Plan d'audit annuel, missions, constats, recommandations — IIA/IFACI/ISO 19011/COSO.", gradient: 'from-cyan-500 to-sky-600', href: '/modules/audit' },
    { icon: PiggyBank, title: 'GED Documentaire', description: 'Gestion électronique des pièces justificatives, validation institutionnelle, classement.', gradient: 'from-indigo-500 to-blue-600', href: '/modules/ged' },
    { icon: FileBarChart, title: 'Reporting institutionnel', description: 'Rapports PDF certifiés, exports Excel, tableaux de bord présidence/sectoriels/budgétaires.', gradient: 'from-orange-500 to-red-600', href: '/modules/reporting' },
];

const standards = [
    { code: 'RBM/GAR', label: 'Gestion Axée sur les Résultats' },
    { code: 'IPSAS', label: 'Comptabilité publique (IPSAS 1 & 24)' },
    { code: 'IIA/IPPF', label: 'International Professional Practices' },
    { code: 'IFACI', label: 'Cadre de référence audit interne' },
    { code: 'ISO 19011', label: 'Audit des systèmes de management' },
    { code: 'COSO ERM', label: 'Enterprise Risk Management 2017' },
    { code: 'RGPD', label: 'Protection des données personnelles' },
    { code: 'OWASP', label: 'Sécurité applicative Top 10' },
];

const stats = [
    { icon: Globe2, value: '11', label: 'États membres', sub: 'Afrique centrale' },
    { icon: Layers, value: '8', label: 'Modules métiers', sub: 'Intégrés nativement' },
    { icon: Target, value: '5', label: 'Niveaux RBM/GAR', sub: 'Chaîne officielle' },
    { icon: ShieldCheck, value: '8+', label: 'Standards internationaux', sub: 'Conformité totale' },
];

export default function Welcome() {
    return (
        <div className="min-h-screen relative overflow-x-hidden bg-background text-foreground">
            <Head title="TB-PAPA — Tableau de bord institutionnel CEEAC" />

            {/* === Decorative background glows === */}
            <div aria-hidden className="pointer-events-none fixed inset-0 -z-10">
                <div className="absolute -top-40 -right-40 h-[480px] w-[480px] rounded-full bg-ceeac-blue/30 blur-3xl" />
                <div className="absolute top-1/3 -left-32 h-[400px] w-[400px] rounded-full bg-ceeac-gold/20 blur-3xl" />
                <div className="absolute bottom-0 right-1/4 h-[420px] w-[420px] rounded-full bg-emerald-500/15 blur-3xl" />
            </div>

            {/* === Header === */}
            <header className="sticky top-0 z-50 border-b border-border/40 bg-background/70 backdrop-blur-xl">
                <div className="container mx-auto flex h-16 items-center justify-between px-4">
                    <Link href="/" className="flex items-center gap-3 group">
                        <div className="relative">
                            <div className="absolute inset-0 bg-ceeac-blue/40 blur-md rounded-lg group-hover:bg-ceeac-blue/60 transition-colors" />
                            <img src="/images/LOGO-CEEAC.jpg" alt="CEEAC" className="relative h-10 w-10 rounded-lg bg-white p-0.5 object-contain shadow-md ring-1 ring-border" />
                        </div>
                        <div className="flex flex-col leading-tight">
                            <span className="font-bold text-sm bg-gradient-to-r from-ceeac-blue to-ceeac-blue/70 bg-clip-text text-transparent">TB-PAPA</span>
                            <span className="text-[10px] text-muted-foreground uppercase tracking-wider">CEEAC · ECCAS</span>
                        </div>
                    </Link>

                    <nav className="hidden md:flex items-center gap-8 text-sm font-medium">
                        <a href="#modules" className="text-muted-foreground hover:text-foreground transition-colors relative group">
                            Modules
                            <span className="absolute -bottom-1 left-0 h-0.5 w-0 bg-ceeac-blue transition-all group-hover:w-full" />
                        </a>
                        <a href="#normes" className="text-muted-foreground hover:text-foreground transition-colors relative group">
                            Conformité
                            <span className="absolute -bottom-1 left-0 h-0.5 w-0 bg-ceeac-blue transition-all group-hover:w-full" />
                        </a>
                        <a href="#contact" className="text-muted-foreground hover:text-foreground transition-colors relative group">
                            Contact
                            <span className="absolute -bottom-1 left-0 h-0.5 w-0 bg-ceeac-blue transition-all group-hover:w-full" />
                        </a>
                    </nav>

                    <Button asChild size="sm" className="shadow-lg shadow-ceeac-blue/20 hover:shadow-ceeac-blue/40 transition-shadow">
                        <Link href="/login">
                            <LogIn className="h-4 w-4" />
                            <span className="hidden sm:inline">Connexion</span>
                        </Link>
                    </Button>
                </div>
            </header>

            {/* === Hero === */}
            <section className="relative overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-ceeac-blue via-ceeac-blue/95 to-indigo-900" />

                {/* Animated mesh pattern */}
                <div className="absolute inset-0 opacity-30" style={{
                    backgroundImage: 'radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(255,193,7,0.15) 0%, transparent 50%)',
                }} />

                {/* Dots overlay */}
                <div className="absolute inset-0 opacity-10" style={{
                    backgroundImage: 'radial-gradient(circle at 20% 20%, white 1px, transparent 1px)',
                    backgroundSize: '40px 40px',
                }} />

                {/* Floating decorative shapes */}
                <div aria-hidden className="absolute top-12 right-12 h-20 w-20 rounded-full border border-white/20 animate-pulse" />
                <div aria-hidden className="absolute bottom-20 left-16 h-32 w-32 rounded-full border border-ceeac-gold/30" style={{ animation: 'pulse 4s ease-in-out infinite' }} />
                <div aria-hidden className="absolute top-1/2 left-1/3 h-3 w-3 rounded-full bg-ceeac-gold/60 animate-ping" />

                <div className="container relative mx-auto px-4 py-20 lg:py-32">
                    <div className="grid grid-cols-1 lg:grid-cols-5 gap-12 items-center">
                        <div className="lg:col-span-3 text-white space-y-7">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] border border-white/20 shadow-lg">
                                <Sparkles className="h-3.5 w-3.5 text-ceeac-gold" />
                                <span>Plateforme institutionnelle CEEAC</span>
                            </div>

                            <h1 className="text-5xl lg:text-7xl font-bold tracking-tight leading-[1.05]">
                                <span className="bg-gradient-to-r from-white via-white to-white/80 bg-clip-text text-transparent">TB</span>
                                <span className="bg-gradient-to-r from-ceeac-gold via-yellow-300 to-ceeac-gold bg-clip-text text-transparent">-PAPA</span>
                                <span className="block text-xl lg:text-2xl font-medium text-white/80 mt-4 leading-snug max-w-2xl">
                                    Tableau de Bord du Plan d'Action Prioritaire Annuel
                                </span>
                            </h1>

                            <p className="text-lg lg:text-xl text-white/85 max-w-2xl leading-relaxed">
                                Plateforme intégrée de planification, exécution et suivi-évaluation du PAPA de la
                                <strong className="text-white"> Commission de la CEEAC</strong>.
                                Conforme aux meilleurs standards internationaux en gestion publique.
                            </p>

                            <div className="flex flex-wrap gap-3 pt-2">
                                <Button asChild size="lg" className="bg-white text-ceeac-blue hover:bg-white/95 hover:shadow-2xl hover:shadow-white/30 hover:-translate-y-0.5 transition-all duration-300 font-semibold">
                                    <Link href="/login">
                                        Accéder à la plateforme
                                        <ArrowRight className="h-4 w-4 ml-1 transition-transform group-hover:translate-x-1" />
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline" className="border-white/40 text-white hover:bg-white/10 hover:border-white/60 backdrop-blur-sm">
                                    <a href="#modules">Découvrir les modules</a>
                                </Button>
                            </div>

                            <div className="flex flex-wrap items-center gap-6 pt-6 text-sm text-white/70">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-ceeac-gold" />
                                    <span>2FA TOTP</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-ceeac-gold" />
                                    <span>Audit trail</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-ceeac-gold" />
                                    <span>Multi-rôles RBAC</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="h-4 w-4 text-ceeac-gold" />
                                    <span>RGPD</span>
                                </div>
                            </div>
                        </div>

                        <div className="lg:col-span-2 flex justify-center lg:justify-end">
                            <div className="relative">
                                {/* Animated glow rings */}
                                <div aria-hidden className="absolute -inset-12 bg-gradient-to-br from-ceeac-gold/40 via-ceeac-gold/20 to-transparent blur-3xl rounded-full animate-pulse" />
                                <div aria-hidden className="absolute -inset-6 border border-white/20 rounded-full" style={{ animation: 'spin 30s linear infinite' }} />
                                <div aria-hidden className="absolute -inset-4 border border-ceeac-gold/30 rounded-full" style={{ animation: 'spin 24s linear infinite reverse' }} />

                                {/* Logo card */}
                                <div className="relative bg-white rounded-3xl p-8 shadow-2xl shadow-black/40 ring-1 ring-white/40">
                                    <img src="/images/LOGO-CEEAC.jpg" alt="Logo CEEAC" className="h-48 w-48 lg:h-64 lg:w-64 object-contain" />
                                </div>

                                {/* Floating badges */}
                                <div className="absolute -top-3 -right-3 bg-ceeac-gold text-ceeac-blue rounded-full px-3 py-1.5 text-xs font-bold shadow-xl shadow-ceeac-gold/30 flex items-center gap-1.5">
                                    <Zap className="h-3 w-3" />
                                    OFFICIEL
                                </div>
                                <div className="absolute -bottom-3 -left-3 bg-emerald-500 text-white rounded-full px-3 py-1.5 text-xs font-bold shadow-xl shadow-emerald-500/30 flex items-center gap-1.5">
                                    <ShieldCheck className="h-3 w-3" />
                                    CERTIFIÉ
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom wave divider */}
                <div className="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-background to-transparent" />
            </section>

            {/* === Stats band === */}
            <section className="relative -mt-10 z-10">
                <div className="container mx-auto px-4">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4 rounded-3xl bg-card/95 backdrop-blur-xl border border-border/60 shadow-2xl shadow-ceeac-blue/10 p-6 md:p-8">
                        {stats.map((s) => {
                            const Icon = s.icon;
                            return (
                                <div key={s.label} className="group text-center md:text-left relative">
                                    <div className="flex items-center justify-center md:justify-start gap-3 mb-2">
                                        <div className="h-10 w-10 rounded-xl bg-gradient-to-br from-ceeac-blue/10 to-ceeac-blue/20 border border-ceeac-blue/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                                            <Icon className="h-5 w-5 text-ceeac-blue" />
                                        </div>
                                        <div className="text-4xl font-bold bg-gradient-to-br from-ceeac-blue to-ceeac-blue/60 bg-clip-text text-transparent tabular-nums">{s.value}</div>
                                    </div>
                                    <div className="text-sm font-semibold mt-1">{s.label}</div>
                                    <div className="text-xs text-muted-foreground">{s.sub}</div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </section>

            {/* === Modules === */}
            <section id="modules" className="container mx-auto px-4 py-24 lg:py-32">
                <div className="text-center mb-14 space-y-4 max-w-3xl mx-auto">
                    <div className="inline-flex items-center gap-2 rounded-full bg-ceeac-blue/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-ceeac-blue border border-ceeac-blue/20">
                        <Layers className="h-3.5 w-3.5" />
                        Modules intégrés
                    </div>
                    <h2 className="text-4xl lg:text-5xl font-bold tracking-tight">
                        Une plateforme,{' '}
                        <span className="bg-gradient-to-r from-ceeac-blue via-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            tout le cycle institutionnel
                        </span>
                    </h2>
                    <p className="text-lg text-muted-foreground leading-relaxed">
                        De la planification stratégique annuelle au suivi-évaluation des recommandations d'audit,
                        TB-PAPA couvre l'ensemble de la chaîne RBM/GAR de la Commission.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    {modules.map((m, idx) => {
                        const Icon = m.icon;
                        return (
                            <Link
                                key={m.title}
                                href={m.href}
                                className="group relative rounded-2xl bg-card border border-border/60 p-6 hover:border-transparent hover:shadow-2xl hover:shadow-ceeac-blue/10 hover:-translate-y-1 transition-all duration-300 overflow-hidden block"
                            >
                                {/* Gradient overlay on hover */}
                                <div className={`absolute inset-0 bg-gradient-to-br ${m.gradient} opacity-0 group-hover:opacity-[0.03] transition-opacity rounded-2xl`} />

                                {/* Number indicator */}
                                <div className="absolute top-4 right-4 text-xs font-bold text-muted-foreground/40">
                                    0{idx + 1}
                                </div>

                                <div className={`mb-5 inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br ${m.gradient} shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300`}>
                                    <Icon className="h-7 w-7 text-white" strokeWidth={2.25} />
                                </div>
                                <h3 className="text-base font-bold leading-tight mb-2 group-hover:text-ceeac-blue transition-colors">{m.title}</h3>
                                <p className="text-sm text-muted-foreground leading-relaxed">{m.description}</p>

                                <div className="mt-4 inline-flex items-center gap-1 text-xs font-semibold text-ceeac-blue opacity-0 group-hover:opacity-100 transition-opacity">
                                    En savoir plus <ArrowRight className="h-3 w-3 transition-transform group-hover:translate-x-1" />
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </section>

            {/* === Standards === */}
            <section id="normes" className="relative py-24 lg:py-32 overflow-hidden">
                <div aria-hidden className="absolute inset-0 -z-10 bg-gradient-to-br from-ceeac-blue/[0.03] via-transparent to-ceeac-gold/[0.05]" />

                <div className="container mx-auto px-4">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                        <div className="space-y-6">
                            <div className="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 border border-emerald-500/20">
                                <ShieldCheck className="h-3.5 w-3.5" />
                                Conformité normative
                            </div>
                            <h2 className="text-4xl lg:text-5xl font-bold tracking-tight leading-[1.1]">
                                Alignée sur{' '}
                                <span className="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
                                    les standards internationaux
                                </span>
                            </h2>
                            <p className="text-lg text-muted-foreground leading-relaxed">
                                TB-PAPA est conçue selon les meilleures pratiques internationales en matière de gestion publique,
                                d'audit interne, de comptabilité publique et de protection des données personnelles.
                            </p>

                            <div className="flex flex-col sm:flex-row gap-3 pt-2">
                                <div className="flex items-center gap-3 rounded-xl bg-card border border-border/60 p-4 flex-1">
                                    <div className="h-10 w-10 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                                        <ShieldCheck className="h-5 w-5 text-emerald-600" />
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold">Sécurité</div>
                                        <div className="text-xs text-muted-foreground">2FA · Audit trail · Soft deletes</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-3 rounded-xl bg-card border border-border/60 p-4 flex-1">
                                    <div className="h-10 w-10 rounded-lg bg-ceeac-blue/10 flex items-center justify-center">
                                        <CheckCircle2 className="h-5 w-5 text-ceeac-blue" />
                                    </div>
                                    <div>
                                        <div className="text-sm font-bold">Traçabilité</div>
                                        <div className="text-xs text-muted-foreground">SHA-256 · QR de vérification</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            {standards.map((s, idx) => (
                                <div
                                    key={s.code}
                                    className="group relative rounded-xl bg-card/80 backdrop-blur-sm border border-border/60 p-4 hover:border-ceeac-blue/40 hover:shadow-lg hover:-translate-y-0.5 transition-all"
                                    style={{ animationDelay: `${idx * 50}ms` }}
                                >
                                    <div className="flex items-start gap-3">
                                        <div className="h-2 w-2 rounded-full bg-gradient-to-br from-ceeac-blue to-ceeac-gold shrink-0 mt-1.5 group-hover:scale-150 transition-transform" />
                                        <div>
                                            <div className="text-sm font-bold text-ceeac-blue">{s.code}</div>
                                            <div className="text-xs text-muted-foreground mt-0.5 leading-snug">{s.label}</div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* === CTA final === */}
            <section className="container mx-auto px-4 py-20 lg:py-28">
                <div className="relative overflow-hidden rounded-3xl border border-primary/20 shadow-2xl shadow-ceeac-blue/20">
                    <div className="absolute inset-0 bg-gradient-to-br from-ceeac-blue via-ceeac-blue/90 to-indigo-900" />

                    {/* Decorative blobs */}
                    <div aria-hidden className="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-ceeac-gold/30 blur-3xl" />
                    <div aria-hidden className="absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl" />

                    {/* Pattern */}
                    <div className="absolute inset-0 opacity-10" style={{
                        backgroundImage: 'radial-gradient(circle at 30% 30%, white 1px, transparent 1px)',
                        backgroundSize: '32px 32px',
                    }} />

                    <div className="relative px-8 py-16 lg:py-20 text-white text-center max-w-3xl mx-auto space-y-6">
                        <div className="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] border border-white/20">
                            <Sparkles className="h-3.5 w-3.5 text-ceeac-gold" />
                            Accès institutionnel sécurisé
                        </div>
                        <h2 className="text-4xl lg:text-5xl font-bold tracking-tight leading-tight">
                            Pilotez le PAPA en{' '}
                            <span className="bg-gradient-to-r from-ceeac-gold via-yellow-300 to-ceeac-gold bg-clip-text text-transparent">
                                temps réel
                            </span>
                        </h2>
                        <p className="text-lg text-white/85 leading-relaxed">
                            Plateforme réservée aux agents habilités de la Commission de la CEEAC.
                            Accès par identifiants institutionnels, sécurisé par authentification à deux facteurs.
                        </p>
                        <div className="flex flex-wrap gap-3 justify-center pt-3">
                            <Button asChild size="lg" className="bg-white text-ceeac-blue hover:bg-white/95 hover:shadow-2xl hover:shadow-white/30 hover:-translate-y-0.5 transition-all duration-300 font-semibold">
                                <Link href="/login">
                                    <LogIn className="h-4 w-4" />
                                    Se connecter
                                    <ArrowRight className="h-4 w-4 ml-1" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            {/* === Footer === */}
            <footer id="contact" className="relative border-t border-border/60 bg-card/40 backdrop-blur-sm">
                <div className="container mx-auto px-4 py-12">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-10">
                        <div className="space-y-4">
                            <div className="flex items-center gap-3">
                                <div className="relative">
                                    <div className="absolute inset-0 bg-ceeac-blue/30 blur-md rounded-lg" />
                                    <img src="/images/LOGO-CEEAC.jpg" alt="CEEAC" className="relative h-12 w-12 rounded-lg bg-white p-1 object-contain ring-1 ring-border shadow-md" />
                                </div>
                                <div>
                                    <div className="font-bold text-base bg-gradient-to-r from-ceeac-blue to-ceeac-blue/70 bg-clip-text text-transparent">TB-PAPA</div>
                                    <div className="text-[10px] text-muted-foreground uppercase tracking-wider">CEEAC · ECCAS</div>
                                </div>
                            </div>
                            <p className="text-xs text-muted-foreground leading-relaxed">
                                Tableau de bord institutionnel du Plan d'Action Prioritaire Annuel de la
                                Commission de la Communauté Économique des États de l'Afrique Centrale.
                            </p>
                        </div>

                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Commission CEEAC</h3>
                            <div className="text-xs text-muted-foreground space-y-2">
                                <p className="flex items-start gap-2">
                                    <Globe2 className="h-3.5 w-3.5 mt-0.5 text-ceeac-blue shrink-0" />
                                    Siège : Libreville, République Gabonaise
                                </p>
                                <p>
                                    <a href="https://ceeac-eccas.org" target="_blank" rel="noopener" className="hover:text-ceeac-blue inline-flex items-center gap-1 group">
                                        ceeac-eccas.org
                                        <ArrowRight className="h-3 w-3 group-hover:translate-x-0.5 transition-transform" />
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div className="space-y-3">
                            <h3 className="text-xs font-bold uppercase tracking-widest text-muted-foreground">Support technique</h3>
                            <div className="text-xs text-muted-foreground space-y-2">
                                <p className="flex items-start gap-2">
                                    <ShieldCheck className="h-3.5 w-3.5 mt-0.5 text-emerald-600 shrink-0" />
                                    Direction des Systèmes d'Information
                                </p>
                                <p>Pour toute assistance, contactez votre administrateur fonctionnel.</p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-10 pt-6 border-t border-border/40 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground">
                        <p>© {new Date().getFullYear()} CEEAC · Communauté Économique des États de l'Afrique Centrale</p>
                        <p className="flex items-center gap-1.5">
                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                            Plateforme institutionnelle · Accès réservé
                        </p>
                    </div>
                </div>
            </footer>
        </div>
    );
}
