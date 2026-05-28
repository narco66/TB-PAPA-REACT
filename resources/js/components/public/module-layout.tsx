import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, LogIn, Sparkles, type LucideIcon } from 'lucide-react';
import { type ReactNode } from 'react';
import { Button } from '@/components/ui/button';

export interface ModuleFeature {
    icon: LucideIcon;
    title: string;
    description: string;
}

export interface ModuleWorkflowStep {
    titre: string;
    description: string;
}

export interface ModuleStandard {
    code: string;
    label: string;
}

export interface ModuleLayoutProps {
    eyebrow: string;
    title: string;
    description: string;
    icon: LucideIcon;
    gradient: string; // ex: 'from-blue-500 to-indigo-600'
    metrics?: Array<{ value: string | number; label: string }>;
    overview: string;
    features: ModuleFeature[];
    workflow?: ModuleWorkflowStep[];
    standards?: ModuleStandard[];
    benefits?: string[];
    children?: ReactNode;
}

export function ModuleLayout({
    eyebrow,
    title,
    description,
    icon: Icon,
    gradient,
    metrics = [],
    overview,
    features,
    workflow,
    standards,
    benefits,
    children,
}: ModuleLayoutProps) {
    return (
        <div className="min-h-screen relative overflow-x-hidden bg-background text-foreground">
            <Head title={`${title} — TB-PAPA CEEAC`} />

            {/* Background glows */}
            <div aria-hidden className="pointer-events-none fixed inset-0 -z-10">
                <div className="absolute -top-40 -right-40 h-[480px] w-[480px] rounded-full bg-ceeac-blue/20 blur-3xl" />
                <div className="absolute top-1/3 -left-32 h-[400px] w-[400px] rounded-full bg-ceeac-gold/10 blur-3xl" />
                <div className="absolute bottom-0 right-1/4 h-[420px] w-[420px] rounded-full bg-emerald-500/10 blur-3xl" />
            </div>

            {/* Header */}
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
                        <Link href="/" className="text-muted-foreground hover:text-foreground transition-colors">Accueil</Link>
                        <Link href="/#modules" className="text-ceeac-blue font-semibold">Modules</Link>
                        <Link href="/#normes" className="text-muted-foreground hover:text-foreground transition-colors">Conformité</Link>
                    </nav>

                    <Button asChild size="sm" className="shadow-lg shadow-ceeac-blue/20">
                        <Link href="/login">
                            <LogIn className="h-4 w-4" />
                            <span className="hidden sm:inline">Connexion</span>
                        </Link>
                    </Button>
                </div>
            </header>

            {/* Hero */}
            <section className="relative overflow-hidden">
                <div className={`absolute inset-0 bg-gradient-to-br ${gradient}`} />
                <div className="absolute inset-0 opacity-30" style={{
                    backgroundImage: 'radial-gradient(circle at 20% 30%, rgba(255,255,255,0.15) 0%, transparent 50%), radial-gradient(circle at 80% 70%, rgba(255,255,255,0.1) 0%, transparent 50%)',
                }} />
                <div className="absolute inset-0 opacity-10" style={{
                    backgroundImage: 'radial-gradient(circle at 20% 20%, white 1px, transparent 1px)',
                    backgroundSize: '32px 32px',
                }} />

                <div aria-hidden className="absolute top-12 right-12 h-20 w-20 rounded-full border border-white/20 animate-pulse" />
                <div aria-hidden className="absolute bottom-20 left-16 h-32 w-32 rounded-full border border-white/15" />

                <div className="container relative mx-auto px-4 py-16 lg:py-24">
                    <Link href="/" className="inline-flex items-center gap-2 text-sm text-white/80 hover:text-white transition-colors mb-6 group">
                        <ArrowLeft className="h-4 w-4 group-hover:-translate-x-1 transition-transform" />
                        Retour à l'accueil
                    </Link>

                    <div className="grid grid-cols-1 lg:grid-cols-5 gap-10 items-center">
                        <div className="lg:col-span-3 text-white space-y-6">
                            <div className="inline-flex items-center gap-2 rounded-full bg-white/10 backdrop-blur-md px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] border border-white/20">
                                <Sparkles className="h-3.5 w-3.5" />
                                {eyebrow}
                            </div>

                            <h1 className="text-4xl lg:text-6xl font-bold tracking-tight leading-[1.1]">
                                {title}
                            </h1>

                            <p className="text-lg lg:text-xl text-white/85 max-w-2xl leading-relaxed">
                                {description}
                            </p>

                            {metrics.length > 0 && (
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-4">
                                    {metrics.map((m) => (
                                        <div key={m.label} className="rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 p-4">
                                            <div className="text-2xl font-bold tabular-nums">{m.value}</div>
                                            <div className="text-xs text-white/75 mt-1">{m.label}</div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="lg:col-span-2 flex justify-center lg:justify-end">
                            <div className="relative">
                                <div aria-hidden className="absolute -inset-10 bg-white/30 blur-3xl rounded-full" />
                                <div aria-hidden className="absolute -inset-4 border border-white/20 rounded-3xl" style={{ animation: 'spin 30s linear infinite' }} />
                                <div className="relative h-44 w-44 lg:h-56 lg:w-56 rounded-3xl bg-white/15 backdrop-blur-md border border-white/30 flex items-center justify-center shadow-2xl">
                                    <Icon className="h-24 w-24 lg:h-32 lg:w-32 text-white" strokeWidth={1.5} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Overview */}
            <section className="container mx-auto px-4 py-16 lg:py-20">
                <div className="max-w-3xl mx-auto text-center space-y-4">
                    <div className="inline-flex items-center gap-2 rounded-full bg-ceeac-blue/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-ceeac-blue border border-ceeac-blue/20">
                        Présentation
                    </div>
                    <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">À propos de ce module</h2>
                    <p className="text-lg text-muted-foreground leading-relaxed">{overview}</p>
                </div>
            </section>

            {/* Features */}
            <section className="container mx-auto px-4 py-16 lg:py-20">
                <div className="text-center mb-12 max-w-3xl mx-auto space-y-3">
                    <div className="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 border border-emerald-500/20">
                        Fonctionnalités clés
                    </div>
                    <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">Ce que propose le module</h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    {features.map((f, idx) => {
                        const FeatureIcon = f.icon;
                        return (
                            <div
                                key={f.title}
                                className="group relative rounded-2xl bg-card border border-border/60 p-6 hover:border-transparent hover:shadow-2xl hover:shadow-ceeac-blue/10 hover:-translate-y-1 transition-all duration-300 overflow-hidden"
                            >
                                <div className={`absolute inset-0 bg-gradient-to-br ${gradient} opacity-0 group-hover:opacity-[0.03] transition-opacity`} />
                                <div className="absolute top-4 right-4 text-xs font-bold text-muted-foreground/40">0{idx + 1}</div>

                                <div className={`mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br ${gradient} shadow-lg group-hover:scale-110 group-hover:rotate-3 transition-transform duration-300`}>
                                    <FeatureIcon className="h-6 w-6 text-white" strokeWidth={2.25} />
                                </div>
                                <h3 className="text-base font-bold leading-tight mb-2 group-hover:text-ceeac-blue transition-colors">{f.title}</h3>
                                <p className="text-sm text-muted-foreground leading-relaxed">{f.description}</p>
                            </div>
                        );
                    })}
                </div>
            </section>

            {/* Workflow */}
            {workflow && workflow.length > 0 && (
                <section className="relative py-16 lg:py-20 overflow-hidden">
                    <div aria-hidden className="absolute inset-0 -z-10 bg-gradient-to-br from-ceeac-blue/[0.03] via-transparent to-ceeac-gold/[0.05]" />
                    <div className="container mx-auto px-4">
                        <div className="text-center mb-12 max-w-3xl mx-auto space-y-3">
                            <div className="inline-flex items-center gap-2 rounded-full bg-violet-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-violet-700 border border-violet-500/20">
                                Cycle / Workflow
                            </div>
                            <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">Comment ça fonctionne</h2>
                        </div>

                        <div className="relative">
                            {/* Vertical line on desktop */}
                            <div aria-hidden className="hidden lg:block absolute left-1/2 top-0 bottom-0 w-px bg-gradient-to-b from-transparent via-ceeac-blue/30 to-transparent -translate-x-1/2" />

                            <div className="space-y-6 lg:space-y-12">
                                {workflow.map((step, idx) => (
                                    <div key={step.titre} className={`lg:grid lg:grid-cols-2 lg:gap-12 items-center ${idx % 2 === 1 ? 'lg:[&>div:first-child]:order-2' : ''}`}>
                                        <div className={`relative ${idx % 2 === 1 ? 'lg:text-left lg:pl-12' : 'lg:text-right lg:pr-12'}`}>
                                            <div className="rounded-2xl bg-card border border-border/60 p-6 shadow-lg hover:shadow-xl transition-shadow">
                                                <div className={`inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br ${gradient} text-white font-bold text-lg mb-3 shadow-md`}>
                                                    {idx + 1}
                                                </div>
                                                <h3 className="text-lg font-bold mb-2">{step.titre}</h3>
                                                <p className="text-sm text-muted-foreground leading-relaxed">{step.description}</p>
                                            </div>
                                        </div>
                                        <div aria-hidden className="hidden lg:flex items-center justify-center">
                                            <div className={`h-4 w-4 rounded-full bg-gradient-to-br ${gradient} ring-4 ring-background shadow-lg`} />
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </section>
            )}

            {/* Benefits + Standards */}
            {(benefits || standards) && (
                <section className="container mx-auto px-4 py-16 lg:py-20">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                        {benefits && (
                            <div className="space-y-6">
                                <div className="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700 border border-emerald-500/20">
                                    Bénéfices
                                </div>
                                <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">
                                    Ce que le module vous{' '}
                                    <span className="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">apporte</span>
                                </h2>
                                <ul className="space-y-3">
                                    {benefits.map((b) => (
                                        <li key={b} className="flex items-start gap-3">
                                            <CheckCircle2 className="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
                                            <span className="text-base leading-relaxed">{b}</span>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}

                        {standards && (
                            <div className="space-y-6">
                                <div className="inline-flex items-center gap-2 rounded-full bg-ceeac-blue/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.2em] text-ceeac-blue border border-ceeac-blue/20">
                                    Conformité normative
                                </div>
                                <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">
                                    Standards{' '}
                                    <span className="bg-gradient-to-r from-ceeac-blue to-indigo-600 bg-clip-text text-transparent">internationaux</span>
                                </h2>
                                <div className="grid grid-cols-1 gap-3">
                                    {standards.map((s) => (
                                        <div key={s.code} className="group flex items-start gap-3 rounded-xl bg-card border border-border/60 p-4 hover:border-ceeac-blue/40 hover:shadow-md transition-all">
                                            <div className="h-2 w-2 rounded-full bg-gradient-to-br from-ceeac-blue to-ceeac-gold shrink-0 mt-2 group-hover:scale-150 transition-transform" />
                                            <div>
                                                <div className="text-sm font-bold text-ceeac-blue">{s.code}</div>
                                                <div className="text-xs text-muted-foreground mt-0.5 leading-snug">{s.label}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </section>
            )}

            {/* Slot pour contenu personnalisé */}
            {children}

            {/* CTA */}
            <section className="container mx-auto px-4 py-20">
                <div className="relative overflow-hidden rounded-3xl border border-primary/20 shadow-2xl shadow-ceeac-blue/20">
                    <div className={`absolute inset-0 bg-gradient-to-br ${gradient}`} />
                    <div aria-hidden className="absolute -top-20 -left-20 h-64 w-64 rounded-full bg-white/20 blur-3xl" />
                    <div aria-hidden className="absolute -bottom-20 -right-20 h-72 w-72 rounded-full bg-white/15 blur-3xl" />
                    <div className="absolute inset-0 opacity-10" style={{
                        backgroundImage: 'radial-gradient(circle at 30% 30%, white 1px, transparent 1px)',
                        backgroundSize: '32px 32px',
                    }} />

                    <div className="relative px-8 py-14 text-white text-center max-w-2xl mx-auto space-y-5">
                        <h2 className="text-3xl lg:text-4xl font-bold tracking-tight">Prêt à découvrir le module ?</h2>
                        <p className="text-base text-white/85 leading-relaxed">
                            Connectez-vous avec vos identifiants institutionnels pour accéder à toutes les fonctionnalités du module
                            « {title} » dans la plateforme TB-PAPA-CEEAC.
                        </p>
                        <div className="flex flex-wrap gap-3 justify-center pt-2">
                            <Button asChild size="lg" className="bg-white text-ceeac-blue hover:bg-white/95 hover:shadow-2xl hover:-translate-y-0.5 transition-all font-semibold">
                                <Link href="/login">
                                    <LogIn className="h-4 w-4" />
                                    Se connecter
                                    <ArrowRight className="h-4 w-4 ml-1" />
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline" className="border-white/40 text-white hover:bg-white/10">
                                <Link href="/#modules">Voir tous les modules</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </section>

            {/* Footer */}
            <footer className="border-t border-border/60 bg-card/40 backdrop-blur-sm">
                <div className="container mx-auto px-4 py-8 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-muted-foreground">
                    <p>© {new Date().getFullYear()} CEEAC · Communauté Économique des États de l'Afrique Centrale</p>
                    <p className="flex items-center gap-1.5">
                        <span className="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse" />
                        Plateforme institutionnelle TB-PAPA
                    </p>
                </div>
            </footer>
        </div>
    );
}
