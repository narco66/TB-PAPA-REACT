import { router } from '@inertiajs/react';
import { AlertOctagon, RefreshCw } from 'lucide-react';
import { Component, type ReactNode } from 'react';

interface Props {
    children: ReactNode;
}

interface State {
    hasError: boolean;
    error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
    public state: State = { hasError: false, error: null };

    public static getDerivedStateFromError(error: Error): State {
        return { hasError: true, error };
    }

    public componentDidCatch(error: Error, errorInfo: React.ErrorInfo): void {
        // eslint-disable-next-line no-console
        console.error('ErrorBoundary caught:', error, errorInfo);
    }

    private reload = () => {
        this.setState({ hasError: false, error: null });
        router.reload();
    };

    private home = () => {
        this.setState({ hasError: false, error: null });
        router.visit('/dashboard');
    };

    public render() {
        if (this.state.hasError) {
            return (
                <div className="flex min-h-screen items-center justify-center bg-background p-6">
                    <div className="w-full max-w-lg rounded-xl border border-destructive/30 bg-card p-6 shadow-lg">
                        <div className="mb-4 flex items-center gap-3">
                            <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                                <AlertOctagon className="h-6 w-6" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold">Erreur d'affichage</h1>
                                <p className="text-sm text-muted-foreground">Une erreur est survenue lors du rendu de cette page.</p>
                            </div>
                        </div>
                        {this.state.error && (
                            <pre className="mb-4 max-h-40 overflow-auto rounded-md bg-muted/40 p-3 text-xs">
                                {this.state.error.message}
                            </pre>
                        )}
                        <div className="flex gap-2">
                            <button
                                onClick={this.reload}
                                className="flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            >
                                <RefreshCw className="h-4 w-4" />
                                Recharger la page
                            </button>
                            <button
                                onClick={this.home}
                                className="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent"
                            >
                                Retour à l'accueil
                            </button>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
