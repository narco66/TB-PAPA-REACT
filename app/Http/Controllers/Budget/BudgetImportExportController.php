<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Models\Budget\BudgetExercice;
use App\Models\Budget\BudgetImport;
use App\Models\Budget\BudgetImportMapping;
use App\Services\Budget\BudgetExportService;
use App\Services\Budget\BudgetImportService;
use App\Services\Budget\BudgetImportTemplateService;
use App\Services\Budget\ExcelAnalyzerService;
use App\Services\Budget\Import\ImportErrorReportXlsxService;
use App\Services\Budget\Import\ImportRollbackService;
use App\Services\Budget\Import\MultiSheetImportOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BudgetImportExportController extends Controller
{
    public function indexImport(Request $request): Response
    {
        $request->user()->can('import_budget') || abort(403);

        return Inertia::render('budget/imports/index', [
            'exercices' => BudgetExercice::orderByDesc('annee')->get(['id', 'annee', 'libelle']),
            'historique' => BudgetImport::with(['exercice:id,annee', 'executePar:id,name'])
                ->whereNull('parent_import_id')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'mappings' => BudgetImportMapping::where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)->orWhere('partage', true);
            })->orderBy('type_donnees')->orderBy('libelle')->get(),
        ]);
    }

    /**
     * Analyse un fichier Excel SANS importer : retourne la structure des feuilles,
     * en-têtes, types détectés et mapping suggéré.
     * Utilisé par l'étape "analyser" de la procédure progressive.
     */
    public function analyser(Request $request, ExcelAnalyzerService $analyzer): JsonResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $request->validate([
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:25600'],
        ]);

        try {
            $analyse = $analyzer->analyser($request->file('fichier'));
        } catch (Throwable $e) {
            return response()->json([
                'succes' => false,
                'message' => "Impossible de lire le fichier : {$e->getMessage()}",
            ], 422);
        }

        return response()->json([
            'succes' => true,
            'analyse' => $analyse,
        ]);
    }

    public function executerImport(Request $request, BudgetImportService $service): RedirectResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $validated = $request->validate([
            'exercice_id' => ['required', 'integer', 'exists:budget_exercices,id'],
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:25600'],
            'dry_run' => ['nullable', 'boolean'],
            'feuille' => ['nullable', 'string', 'max:64'],
        ]);

        $exercice = BudgetExercice::findOrFail($validated['exercice_id']);
        if ($exercice->estVerrouille()) {
            return back()->with('error', 'Cet exercice est verrouillé, import impossible.');
        }

        $chemin = $request->file('fichier')->store('budget-imports', 'local');
        $cheminAbsolu = Storage::disk('local')->path($chemin);

        try {
            $resultat = $service->importer(
                $cheminAbsolu,
                $exercice,
                $request->user()->id,
                $request->boolean('dry_run'),
                $validated['feuille'] ?? null,
            );
        } catch (Throwable $e) {
            return back()->with('error', 'Erreur fatale : ' . $e->getMessage());
        }

        if (! $resultat['succes']) {
            return back()
                ->with('error', 'Import refusé — ' . count($resultat['erreurs']) . ' erreur(s).')
                ->with('import_result', $resultat);
        }

        $modeStr = $request->boolean('dry_run') ? ' (prévisualisation)' : '';

        return back()
            ->with('success', "Import OK{$modeStr} : {$resultat['stats']['valides']} lignes valides, {$resultat['stats']['creees']} lignes créées.")
            ->with('import_result', $resultat);
    }

    public function modele(Request $request, BudgetImportTemplateService $service): BinaryFileResponse
    {
        $request->user()->can('import_budget') || abort(403);

        return response()
            ->download($service->generer(), 'modele-import-budget-tb-papa-ceeac.xlsx')
            ->deleteFileAfterSend(false);
    }

    public function rapportErreurs(Request $request, BudgetImport $import): BinaryFileResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $journal = $import->journal ?? [];
        $path = storage_path("app/budget-imports/rapport-erreurs-import-{$import->id}.csv");
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $out = fopen($path, 'w');
        fputcsv($out, ['feuille', 'ligne', 'colonne', 'gravite', 'regle', 'message', 'correction_suggeree'], ';');

        // Préférer les erreurs structurées (table dédiée), sinon retomber sur le journal JSON.
        $erreursStructurees = $import->erreurs()->orderBy('ligne')->get();
        if ($erreursStructurees->isNotEmpty()) {
            foreach ($erreursStructurees as $err) {
                fputcsv($out, [
                    $err->feuille ?? '',
                    $err->ligne ?? '',
                    $err->colonne ?? '',
                    $err->gravite,
                    $err->regle,
                    $err->message,
                    $err->correction_suggeree ?? '',
                ], ';');
            }
        } else {
            foreach (($journal['erreurs'] ?? []) as $erreur) {
                fputcsv($out, [
                    '',
                    $erreur['ligne'] ?? '',
                    $erreur['champ'] ?? '',
                    'erreur',
                    'autre',
                    $erreur['message'] ?? (string) $erreur,
                    '',
                ], ';');
            }
        }
        fclose($out);

        return response()
            ->download($path, "rapport-erreurs-import-{$import->id}.csv")
            ->deleteFileAfterSend(false);
    }

    /**
     * Exécute un import RBM multi-feuilles (Axes, Produits, Sous-Produits, Activités, Tâches, Indicateurs).
     * Le BudgetImportService existant continue d'être utilisé pour les feuilles "budget" via le formulaire classique.
     */
    public function executerMultiFeuilles(Request $request, MultiSheetImportOrchestrator $orchestrator): RedirectResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $validated = $request->validate([
            'exercice_id' => ['required', 'integer', 'exists:budget_exercices,id'],
            'fichier' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:25600'],
            'dry_run' => ['nullable', 'boolean'],
            'feuilles' => ['required', 'array', 'min:1'],
            'feuilles.*.nom' => ['required', 'string', 'max:64'],
            'feuilles.*.type' => ['required', Rule::in(['axes', 'produits', 'sous_produits', 'activites', 'taches', 'indicateurs'])],
            'feuilles.*.mapping' => ['nullable', 'array'],
        ]);

        $exercice = BudgetExercice::findOrFail($validated['exercice_id']);
        if ($exercice->estVerrouille()) {
            return back()->with('error', 'Cet exercice est verrouillé, import impossible.');
        }

        $file = $request->file('fichier');
        // Calcul du hash AVANT le store() (le fichier d'origine est toujours accessible à ce stade)
        $hash = $file->getRealPath() && file_exists($file->getRealPath())
            ? (hash_file('sha256', $file->getRealPath()) ?: '')
            : '';
        $chemin = $file->store('budget-imports', 'local');
        $cheminAbsolu = Storage::disk('local')->path($chemin);
        if (! file_exists($cheminAbsolu) && $file->getRealPath()) {
            $cheminAbsolu = $file->getRealPath();
        }

        try {
            $parent = $orchestrator->executer(
                $exercice,
                $cheminAbsolu,
                $file->getClientOriginalName(),
                $file->getSize(),
                $hash,
                $request->user()->id,
                $validated['feuilles'],
                null,
                $request->boolean('dry_run'),
            );
        } catch (Throwable $e) {
            return back()->with('error', 'Erreur fatale : ' . $e->getMessage());
        }

        $msgPrefix = $request->boolean('dry_run') ? 'Prévisualisation' : 'Import';
        $msg = "{$msgPrefix} multi-feuilles : {$parent->nb_lignes_creees} créées · {$parent->nb_lignes_mises_a_jour} mises à jour · {$parent->nb_erreurs} erreur(s)";

        if ($parent->statut === 'echec') {
            return back()->with('error', "Import refusé — {$parent->nb_erreurs} erreur(s).");
        }

        return back()->with('success', $msg);
    }

    /**
     * Sauvegarde un mapping de colonnes réutilisable pour l'utilisateur.
     */
    public function saveMapping(Request $request): JsonResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $validated = $request->validate([
            'type_donnees' => ['required', Rule::in(BudgetImport::TYPES_DONNEES)],
            'libelle' => ['required', 'string', 'max:128'],
            'mapping' => ['required', 'array'],
            'partage' => ['nullable', 'boolean'],
        ]);

        $mapping = BudgetImportMapping::create([
            'user_id' => $request->user()->id,
            'type_donnees' => $validated['type_donnees'],
            'libelle' => $validated['libelle'],
            'mapping' => $validated['mapping'],
            'partage' => $request->boolean('partage'),
        ]);

        return response()->json(['succes' => true, 'mapping' => $mapping]);
    }

    /**
     * Renvoie le statut d'un import (utilisé pour le polling après dispatch d'un Job async).
     */
    public function statut(Request $request, BudgetImport $import): JsonResponse
    {
        $request->user()->can('import_budget') || abort(403);

        return response()->json([
            'id' => $import->id,
            'statut' => $import->statut,
            'nb_lignes_lues' => $import->nb_lignes_lues,
            'nb_lignes_creees' => $import->nb_lignes_creees,
            'nb_lignes_mises_a_jour' => $import->nb_lignes_mises_a_jour,
            'nb_erreurs' => $import->nb_erreurs,
            'feuilles' => $import->enfants()->get(['feuille_source', 'type_donnees', 'statut', 'nb_lignes_creees', 'nb_erreurs']),
        ]);
    }

    /**
     * Annule un import multi-feuilles : soft-delete les ressources créées par cet import.
     * Restreint à admin technique pour préserver la traçabilité.
     */
    public function rollback(Request $request, BudgetImport $import, ImportRollbackService $rollback): RedirectResponse
    {
        $request->user()->can('import_budget') || abort(403);
        if (! $request->user()->hasRole('admin_technique')) {
            abort(403, 'Seul un administrateur technique peut annuler un import.');
        }

        if ($import->statut === 'rollback') {
            return back()->with('error', 'Cet import a déjà été annulé.');
        }

        $resultat = $rollback->annulerImport($import);

        return back()->with('success',
            "Import #{$import->id} annulé : {$resultat['total']} ressource(s) supprimée(s).",
        );
    }

    /**
     * Génère un rapport d'erreurs XLSX riche (coloré, feuilles synthèse + détail).
     */
    public function rapportErreursXlsx(Request $request, BudgetImport $import, ImportErrorReportXlsxService $service): BinaryFileResponse
    {
        $request->user()->can('import_budget') || abort(403);

        $chemin = $service->genererPour($import);

        return response()
            ->download($chemin, "rapport-erreurs-import-{$import->id}.xlsx")
            ->deleteFileAfterSend(true);
    }

    /**
     * Supprime un mapping (auteur ou admin technique uniquement).
     */
    public function deleteMapping(Request $request, BudgetImportMapping $mapping): JsonResponse
    {
        $request->user()->can('import_budget') || abort(403);
        if ($mapping->user_id !== $request->user()->id && ! $request->user()->hasRole('admin_technique')) {
            abort(403, 'Vous ne pouvez supprimer que vos propres mappings.');
        }

        $mapping->delete();

        return response()->json(['succes' => true]);
    }

    public function exporter(Request $request, BudgetExportService $service): BinaryFileResponse
    {
        $request->user()->can('export_budget') || abort(403);

        $validated = $request->validate([
            'exercice_id' => ['required', 'integer', 'exists:budget_exercices,id'],
            'template' => ['nullable', Rule::in(BudgetExportService::TEMPLATES)],
            'format' => ['nullable', Rule::in(BudgetExportService::FORMATS)],
        ]);

        $exercice = BudgetExercice::findOrFail($validated['exercice_id']);
        $chemin = $service->exporter(
            $exercice,
            $validated['template'] ?? 'consolide',
            $validated['format'] ?? 'xlsx',
        );

        return response()->download($chemin)->deleteFileAfterSend(false);
    }
}
