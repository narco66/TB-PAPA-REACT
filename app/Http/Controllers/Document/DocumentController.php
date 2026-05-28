<?php

namespace App\Http\Controllers\Document;

use App\Http\Controllers\Controller;
use App\Models\ActionPrioritaire;
use App\Models\Activite;
use App\Models\Document;
use App\Models\Indicateur;
use App\Models\Papa;
use App\Models\ResultatAttendu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public const ATTACHABLES = [
        'papa' => Papa::class,
        'action' => ActionPrioritaire::class,
        'resultat' => ResultatAttendu::class,
        'indicateur' => Indicateur::class,
        'activite' => Activite::class,
    ];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Document::class);

        $query = Document::query()
            ->with(['uploadePar:id,name', 'validePar:id,name', 'documentable']);

        // Cacher les documents confidentiels aux non-habilités
        if (! $request->user()->can('document.viewConfidential')) {
            $query->where('confidentiel', false);
        }

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('libelle', 'like', "%{$q}%")
                ->orWhere('nom_fichier', 'like', "%{$q}%"),
            );
        }
        if ($cat = $request->string('categorie')->toString()) {
            $query->where('categorie', $cat);
        }
        if ($valide = $request->string('valide')->toString()) {
            $query->when($valide === 'oui', fn ($w) => $w->whereNotNull('valide_at'))
                ->when($valide === 'non', fn ($w) => $w->whereNull('valide_at'));
        }

        $documents = $query->orderByDesc('id')->paginate(25)->withQueryString();

        return Inertia::render('documents/index', [
            'documents' => $documents,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'categorie' => $request->string('categorie')->toString(),
                'valide' => $request->string('valide')->toString(),
            ],
            'can' => [
                'upload' => $request->user()->can('document.upload'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('upload', Document::class);

        return Inertia::render('documents/create', [
            'attachables' => [
                'papa' => Papa::orderByDesc('annee')->get(['id', 'libelle as libelle', 'annee']),
                'action' => ActionPrioritaire::with('papa:id,annee')->get(['id', 'code', 'libelle', 'papa_id']),
                'activite' => Activite::with('actionPrioritaire:id,code')->get(['id', 'code', 'libelle', 'action_prioritaire_id']),
                'indicateur' => Indicateur::get(['id', 'code', 'libelle']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('upload', Document::class);

        $validated = $request->validate([
            'documentable_type' => ['required', Rule::in(array_keys(self::ATTACHABLES))],
            'documentable_id' => ['required', 'integer'],
            'categorie' => ['required', Rule::in(['execution', 'validation', 'financier', 'suivi_evaluation', 'autre'])],
            'libelle' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'confidentiel' => ['nullable', 'boolean'],
            'fichier' => ['required', 'file', 'max:25600'], // 25 Mo
        ]);

        $classe = self::ATTACHABLES[$validated['documentable_type']];
        $entite = $classe::findOrFail($validated['documentable_id']);

        $file = $request->file('fichier');
        $hash = hash_file('sha256', $file->getRealPath());
        $path = $file->store('ged/' . date('Y/m'), 'local');

        $document = Document::create([
            'documentable_type' => $classe,
            'documentable_id' => $entite->id,
            'categorie' => $validated['categorie'],
            'libelle' => $validated['libelle'],
            'description' => $validated['description'] ?? null,
            'nom_fichier' => $file->getClientOriginalName(),
            'chemin_stockage' => $path,
            'mime_type' => $file->getMimeType(),
            'taille_octets' => $file->getSize(),
            'hash_sha256' => $hash,
            'uploade_par_id' => $request->user()->id,
            'confidentiel' => (bool) ($validated['confidentiel'] ?? false),
            'version' => 1,
        ]);

        return redirect()
            ->route('documents.index')
            ->with('success', "Document « {$document->libelle} » déposé dans la GED.");
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless(Storage::disk('local')->exists($document->chemin_stockage), 404);

        return Storage::disk('local')->download($document->chemin_stockage, $document->nom_fichier);
    }

    public function valider(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('validate', $document);

        $document->update([
            'valide_at' => now(),
            'valide_par_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Document validé et certifié.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        Storage::disk('local')->delete($document->chemin_stockage);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }
}
