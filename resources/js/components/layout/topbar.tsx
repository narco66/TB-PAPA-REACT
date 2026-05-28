import { Link, router, usePage } from '@inertiajs/react';
import { LogOut, User as UserIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PageProps } from '@/types';

const ROLE_LIBELLES: Record<string, string> = {
    president: 'Président',
    vice_president: 'Vice-Président',
    commissaire: 'Commissaire',
    secretaire_general: 'Secrétaire Général',
    directeur_technique: 'Directeur technique',
    directeur_appui: 'Directeur d\'appui',
    chef_service: 'Chef de service',
    point_focal: 'Point focal',
    audit_interne: 'Audit interne',
    controle_financier: 'Contrôle financier',
    partenaire: 'Partenaire',
    admin_fonctionnel: 'Admin fonctionnel',
    admin_technique: 'Admin technique',
};

export function Topbar({ title }: { title?: string }) {
    const { auth } = usePage<PageProps>().props;
    const user = auth.user;
    const principalRole = user?.roles[0];

    const initiales = user?.name
        ?.split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    return (
        <header className="sticky top-0 z-30 flex h-16 items-center justify-between border-b bg-background/80 px-4 backdrop-blur-md md:px-6">
            <div className="flex flex-col">
                {title ? <h1 className="text-lg font-semibold tracking-tight">{title}</h1> : null}
                <p className="text-xs text-muted-foreground">
                    Commission de la Communauté Économique des États de l'Afrique Centrale
                </p>
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="flex h-auto items-center gap-3 px-2 py-1.5">
                        <div className="hidden text-right md:block">
                            <p className="text-sm font-medium leading-tight">{user?.name}</p>
                            <p className="text-xs text-muted-foreground">{principalRole ? ROLE_LIBELLES[principalRole] ?? principalRole : ''}</p>
                        </div>
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold text-xs">
                            {initiales}
                        </div>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-56">
                    <DropdownMenuLabel className="font-normal">
                        <div className="flex flex-col">
                            <span className="text-sm font-semibold">{user?.name}</span>
                            <span className="text-xs text-muted-foreground">{user?.email}</span>
                            {user?.matricule ? (
                                <span className="text-xs text-muted-foreground">Mat. {user.matricule}</span>
                            ) : null}
                        </div>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem asChild>
                        <Link href="#" className="flex items-center gap-2">
                            <UserIcon className="h-4 w-4" />
                            <span>Mon profil</span>
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        className="text-destructive focus:text-destructive"
                        onSelect={() => router.post('/logout')}
                    >
                        <LogOut className="h-4 w-4" />
                        <span>Se déconnecter</span>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </header>
    );
}
