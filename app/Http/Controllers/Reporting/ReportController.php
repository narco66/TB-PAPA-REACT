<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Models\GeneratedReport;
use App\Reports\Report;
use App\Services\Reporting\PdfGeneratorService;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportRegistry $registry,
        protected PdfGeneratorService $pdfGenerator,
    ) {}

    public function index(Request $request): Response
    {
        $request->user()->can('generate_reports') || abort(403);

        $categories = [];
        foreach ($this->registry->parCategorie() as $cat => $reports) {
            $categories[] = [
                'key' => $cat,
                'libelle' => Report::CATEGORIES[$cat] ?? $cat,
                'rapports' => array_map(fn (Report $r) => [
                    'key' => $r->key(),
                    'titre' => $r->titre(),
                    'description' => $r->description(),
                    'icone' => $r->icone(),
                ], $reports),
            ];
        }

        $historique = GeneratedReport::with('genereur:id,name')
            ->orderByDesc('genere_at')
            ->limit(20)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'titre' => $r->titre,
                'categorie' => $r->categorie,
                'genere_par' => $r->genereur?->name,
                'genere_at' => $r->genere_at?->toIso8601String(),
                'taille_octets' => $r->taille_octets,
                'nb_telechargements' => $r->nb_telechargements,
            ]);

        return Inertia::render('reports/index', [
            'categories' => $categories,
            'historique' => $historique,
        ]);
    }

    public function show(Request $request, string $key): Response
    {
        $request->user()->can('generate_reports') || abort(403);

        $report = $this->registry->trouver($key);

        return Inertia::render('reports/generer', [
            'rapport' => [
                'key' => $report->key(),
                'titre' => $report->titre(),
                'description' => $report->description(),
                'categorie' => $report->categorie(),
                'icone' => $report->icone(),
                'orientation' => $report->orientation(),
                'format' => $report->format(),
            ],
            'filtres' => $report->filtres(),
        ]);
    }

    public function generer(Request $request, string $key): RedirectResponse
    {
        $request->user()->can('generate_reports') || abort(403);

        $report = $this->registry->trouver($key);
        $filtres = $request->validate([
            'filtres' => 'nullable|array',
        ])['filtres'] ?? [];

        try {
            $generated = $this->pdfGenerator->generer($report, $filtres, $request->user()->id);

            return redirect()->route('reports.historique')
                ->with('success', "Rapport « {$report->titre()} » généré avec succès (code : {$generated->code_verification}).");
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec de génération : ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Génération rapide contextuelle : génère le rapport avec les query parameters
     * comme filtres puis redirige immédiatement vers le téléchargement.
     * Utilisé par les boutons PDF natifs intégrés dans les pages métier.
     *
     * Exemple : GET /rapports/fiche_axe/quick?axe_id=42
     */
    public function quick(Request $request, string $key): BinaryFileResponse|RedirectResponse
    {
        $request->user()->can('generate_reports') || abort(403);

        try {
            $report = $this->registry->trouver($key);

            // Les query parameters deviennent les filtres
            $filtres = $request->except(['_token']);

            $generated = $this->pdfGenerator->generer($report, $filtres, $request->user()->id);
            $chemin = $this->pdfGenerator->telecharger($generated, $request->user()->id);

            return response()->download($chemin, $generated->nom_fichier, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $generated->nom_fichier . '"',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec de génération PDF : ' . $e->getMessage());
        }
    }

    public function historique(Request $request): Response
    {
        $request->user()->can('generate_reports') || abort(403);

        $reports = GeneratedReport::with('genereur:id,name', 'dernierTelechargeur:id,name')
            ->orderByDesc('genere_at')
            ->when($request->string('categorie')->toString(), fn ($q, $cat) => $q->where('categorie', $cat))
            ->when($request->string('q')->trim()->toString(), fn ($q, $s) => $q->where('titre', 'like', "%{$s}%"))
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('reports/historique', [
            'rapports' => $reports,
            'filtres' => [
                'q' => $request->string('q')->toString(),
                'categorie' => $request->string('categorie')->toString(),
            ],
            'categories' => Report::CATEGORIES,
        ]);
    }

    public function telecharger(Request $request, GeneratedReport $rapport): BinaryFileResponse
    {
        $request->user()->can('download_reports') || $request->user()->can('generate_reports') || abort(403);

        $chemin = $this->pdfGenerator->telecharger($rapport, $request->user()->id);

        return response()->download($chemin, $rapport->nom_fichier);
    }

    public function preview(Request $request, GeneratedReport $rapport): \Symfony\Component\HttpFoundation\Response
    {
        $request->user()->can('download_reports') || $request->user()->can('generate_reports') || abort(403);

        $chemin = Storage::disk('local')->path($rapport->chemin_stockage);
        abort_unless(file_exists($chemin), 404);

        return response()->file($chemin, ['Content-Type' => 'application/pdf']);
    }

    public function verifier(string $code): Response
    {
        $rapport = GeneratedReport::where('code_verification', $code)->first();

        return Inertia::render('reports/verifier', [
            'rapport' => $rapport ? [
                'titre' => $rapport->titre,
                'categorie' => $rapport->categorie,
                'genere_at' => $rapport->genere_at?->toIso8601String(),
                'genere_par' => $rapport->genereur?->name,
                'hash_sha256' => $rapport->hash_sha256,
                'code_verification' => $rapport->code_verification,
                'authentique' => true,
            ] : null,
            'code' => $code,
        ]);
    }
}
