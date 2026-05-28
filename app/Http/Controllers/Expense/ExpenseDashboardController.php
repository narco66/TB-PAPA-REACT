<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Services\Expense\DashboardExpenseStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseDashboardController extends Controller
{
    public function __construct(protected DashboardExpenseStatsService $stats) {}

    public function __invoke(Request $request): Response
    {
        $this->authorize('expense.viewAny');

        $exerciceId = $request->integer('exercice_id') ?: null;

        return Inertia::render('expense/dashboard', [
            'stats' => $this->stats->build($exerciceId),
        ]);
    }
}
