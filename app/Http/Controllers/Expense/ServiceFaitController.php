<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Budget\BudgetMouvement;
use App\Models\Reception;
use App\Models\ServiceDoneCertificate;
use App\Services\Expense\ServiceFaitReceptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceFaitController extends Controller
{
    public function __construct(protected ServiceFaitReceptionService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize('expense.viewAny');

        $certificats = ServiceDoneCertificate::query()
            ->with(['mouvement:id,reference,montant', 'constatePar:id,name'])
            ->when($request->input('statut'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $receptions = Reception::query()
            ->with(['mouvement:id,reference', 'saisiPar:id,name'])
            ->when($request->input('statut_pv'), fn ($q, $s) => $q->where('statut', $s))
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('expense/service-fait/index', [
            'certificats' => $certificats,
            'receptions' => $receptions,
            'conformites' => ServiceDoneCertificate::CONFORMITES,
            'statuts' => ServiceDoneCertificate::STATUTS,
            'types_reception' => Reception::TYPES,
            'natures' => Reception::NATURES,
            'filters' => $request->only(['statut', 'statut_pv']),
        ]);
    }

    public function storeCertificat(Request $request): RedirectResponse
    {
        $this->authorize('expense.engage');

        $data = $request->validate([
            'budget_mouvement_id' => ['required', 'integer', 'exists:budget_mouvements,id'],
            'date_constatation' => ['required', 'date'],
            'description' => ['required', 'string', 'max:5000'],
            'montant_constate' => ['required', 'numeric', 'min:0.01'],
            'conformite_qualitative' => ['required', Rule::in(ServiceDoneCertificate::CONFORMITES)],
            'conformite_quantitative' => ['required', Rule::in(ServiceDoneCertificate::CONFORMITES)],
            'observations' => ['nullable', 'string', 'max:5000'],
        ]);

        $engagement = BudgetMouvement::findOrFail($data['budget_mouvement_id']);

        try {
            $cert = $this->service->creerCertificatServiceFait($engagement, $data, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec création service fait : ' . $e->getMessage());
        }

        return back()->with('success', "Certificat de service fait {$cert->reference} créé.");
    }

    public function validateCertificat(ServiceDoneCertificate $certificat, Request $request): RedirectResponse
    {
        $this->authorize('expense.validate_hierarchique');

        try {
            $this->service->validerCertificat($certificat, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Certificat {$certificat->reference} validé.");
    }

    public function storeReception(Request $request): RedirectResponse
    {
        $this->authorize('expense.engage');

        $data = $request->validate([
            'budget_mouvement_id' => ['required', 'integer', 'exists:budget_mouvements,id'],
            'service_done_certificate_id' => ['nullable', 'integer', 'exists:service_done_certificates,id'],
            'date_reception' => ['required', 'date'],
            'type_reception' => ['required', Rule::in(Reception::TYPES)],
            'nature' => ['required', Rule::in(Reception::NATURES)],
            'quantite_recue' => ['nullable', 'numeric', 'min:0'],
            'unite_mesure' => ['nullable', 'string', 'max:32'],
            'montant_recu' => ['required', 'numeric', 'min:0'],
            'conformite' => ['required', Rule::in(Reception::CONFORMITES)],
            'reserves' => ['nullable', 'string', 'max:5000'],
            'observations' => ['nullable', 'string', 'max:5000'],
            'president_commission_id' => ['nullable', 'integer', 'exists:users,id'],
            'membre1_commission_id' => ['nullable', 'integer', 'exists:users,id'],
            'membre2_commission_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $engagement = BudgetMouvement::findOrFail($data['budget_mouvement_id']);

        try {
            $reception = $this->service->creerReception($engagement, $data, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', 'Échec création réception : ' . $e->getMessage());
        }

        return back()->with('success', "Procès-verbal {$reception->reference} créé.");
    }

    public function validateReception(Reception $reception, Request $request): RedirectResponse
    {
        $this->authorize('expense.validate_hierarchique');

        try {
            $this->service->validerReception($reception, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Réception {$reception->reference} validée.");
    }
}
