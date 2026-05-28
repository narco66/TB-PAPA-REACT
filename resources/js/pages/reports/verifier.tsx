import { Head } from '@inertiajs/react';
import { CheckCircle2, ShieldAlert, ShieldCheck } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { formatDate } from '@/lib/utils';

interface Props {
    rapport: null | {
        titre: string;
        categorie: string;
        genere_at: string;
        genere_par: string | null;
        hash_sha256: string;
        code_verification: string;
        authentique: boolean;
    };
    code: string;
}

export default function ReportVerifier({ rapport, code }: Props) {
    return (
        <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-ceeac-blue/5 via-background to-ceeac-gold/5 p-4">
            <Head title="Vérification de document" />

            <div className="w-full max-w-2xl">
                <div className="mb-6 text-center">
                    <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-2xl bg-ceeac-blue shadow-lg">
                        <ShieldCheck className="h-8 w-8 text-white" />
                    </div>
                    <h1 className="text-2xl font-bold">Vérification de document</h1>
                    <p className="text-sm text-muted-foreground">Système TB-PAPA-CEEAC — Commission de la CEEAC</p>
                </div>

                <Card>
                    <CardContent className="p-6">
                        {rapport ? (
                            <>
                                <div className="mb-4 flex items-center justify-center gap-3">
                                    <CheckCircle2 className="h-12 w-12 text-success" />
                                    <div>
                                        <h2 className="text-xl font-semibold text-success">Document authentique</h2>
                                        <p className="text-sm text-muted-foreground">Vérifié dans le registre institutionnel.</p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 gap-3 border-t pt-4 text-sm">
                                    <Field label="Titre du document" value={rapport.titre} />
                                    <Field label="Catégorie" value={<Badge variant="secondary">{rapport.categorie}</Badge>} />
                                    <Field label="Émis le" value={formatDate(rapport.genere_at)} />
                                    <Field label="Émis par" value={rapport.genere_par ?? 'Système'} />
                                    <Field label="Code de vérification" value={<code className="font-mono text-xs">{rapport.code_verification}</code>} />
                                    <Field label="Hash SHA-256" value={<code className="font-mono text-[10px] break-all">{rapport.hash_sha256}</code>} />
                                </div>

                                <div className="mt-6 rounded-md border bg-muted/30 p-3 text-xs text-muted-foreground">
                                    Ce document est enregistré dans le registre institutionnel TB-PAPA-CEEAC.
                                    Son intégrité est garantie par un hash SHA-256 immuable.
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="mb-4 flex items-center justify-center gap-3">
                                    <ShieldAlert className="h-12 w-12 text-destructive" />
                                    <div>
                                        <h2 className="text-xl font-semibold text-destructive">Document introuvable</h2>
                                        <p className="text-sm text-muted-foreground">
                                            Le code de vérification <code className="font-mono text-xs">{code}</code> ne correspond à aucun document dans notre registre.
                                        </p>
                                    </div>
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function Field({ label, value }: { label: string; value: any }) {
    return (
        <div className="grid grid-cols-3 gap-3">
            <span className="text-xs font-medium text-muted-foreground">{label}</span>
            <span className="col-span-2">{value}</span>
        </div>
    );
}
