<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departement;
use App\Models\Direction;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganigrammeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('departement.manage');

        $departements = Departement::query()
            ->with([
                'commissaire:id,name,fonction',
                'directions' => fn ($q) => $q->orderBy('libelle'),
                'directions.directeur:id,name,fonction',
                'directions.services' => fn ($q) => $q->orderBy('libelle'),
                'directions.services.chefService:id,name,fonction',
            ])
            ->withCount(['directions', 'services', 'axes'])
            ->orderBy('ordre')
            ->get();

        $statsGlobales = [
            'departements' => Departement::count(),
            'directions' => Direction::count(),
            'services' => Service::count(),
            'agents' => User::where('actif', true)->count(),
        ];

        return Inertia::render('admin/organigramme', [
            'departements' => $departements,
            'stats' => $statsGlobales,
        ]);
    }
}
