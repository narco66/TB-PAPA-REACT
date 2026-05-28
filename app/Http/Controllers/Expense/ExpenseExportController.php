<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Services\Expense\ExpenseJournalExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExpenseExportController extends Controller
{
    public function __construct(protected ExpenseJournalExportService $service) {}

    public function download(string $journal, string $format, Request $request): BinaryFileResponse
    {
        $this->authorize('expense.viewAny');

        if (! in_array($journal, ExpenseJournalExportService::JOURNAUX, true)) {
            abort(404, 'Journal inconnu.');
        }
        if (! in_array($format, ExpenseJournalExportService::FORMATS, true)) {
            abort(404, 'Format non supporté.');
        }

        $filtres = $request->only(['statut', 'type', 'exercice_id']);

        try {
            $path = $this->service->exporter($journal, $format, $filtres);
        } catch (\Throwable $e) {
            abort(500, 'Échec génération export : ' . $e->getMessage());
        }

        $filename = 'tbpapa-' . $journal . '-' . now()->format('Ymd-His') . '.' . $format;

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
