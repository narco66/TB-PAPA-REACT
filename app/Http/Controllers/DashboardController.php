<?php

namespace App\Http\Controllers;

use App\Models\Papa;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected DashboardStatsService $stats) {}

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $papa = Papa::actif()->orderByDesc('annee')->first();

        return Inertia::render('dashboard/index', [
            'stats' => $this->stats->build($papa),
            'niveau_utilisateur' => $user->niveauAutorite(),
            'roles' => $user->getRoleNames()->toArray(),
            'can' => [
                'papa_create' => $user->can('papa.create'),
                'view_audit' => $user->can('audit.viewLog'),
                'view_budget' => $user->can('budget.viewAny'),
            ],
        ]);
    }
}
