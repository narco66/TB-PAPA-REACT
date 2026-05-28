<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\EngagerRequest;
use App\Http\Requests\Budget\LiquiderRequest;
use App\Http\Requests\Budget\OrdonnancerRequest;
use App\Http\Requests\Budget\PayerRequest;
use App\Models\Budget\BudgetLigne;
use App\Models\Budget\BudgetMouvement;
use App\Services\Budget\BudgetCycleService;
use Illuminate\Http\RedirectResponse;
use Throwable;

class BudgetCycleController extends Controller
{
    public function __construct(protected BudgetCycleService $cycle) {}

    public function engager(EngagerRequest $request, BudgetLigne $ligne): RedirectResponse
    {
        try {
            $this->cycle->engager(
                $ligne,
                (float) $request->validated()['montant'],
                $request->user(),
                $request->validated()['beneficiaire_nom'] ?? null,
                $request->validated()['beneficiaire_reference'] ?? null,
                $request->validated()['partenaire_id'] ?? null,
                $request->validated()['numero_piece'] ?? null,
                $request->validated()['motif'] ?? null,
                $request->validated()['piece_justificative'] ?? null,
            );

            return back()->with('success', 'Engagement enregistré.');
        } catch (Throwable $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }
    }

    public function liquider(LiquiderRequest $request, BudgetMouvement $mouvement): RedirectResponse
    {
        try {
            $this->cycle->liquider(
                $mouvement,
                (float) $request->validated()['montant'],
                $request->user(),
                $request->validated()['numero_piece'] ?? null,
                $request->validated()['motif'] ?? null,
                $request->validated()['piece_justificative'] ?? null,
            );

            return back()->with('success', 'Liquidation enregistrée.');
        } catch (Throwable $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }
    }

    public function ordonnancer(OrdonnancerRequest $request, BudgetMouvement $mouvement): RedirectResponse
    {
        try {
            $this->cycle->ordonnancer(
                $mouvement,
                $request->user(),
                $request->validated()['numero_piece'] ?? null,
                $request->validated()['motif'] ?? null,
            );

            return back()->with('success', 'Ordonnancement enregistré.');
        } catch (Throwable $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }
    }

    public function payer(PayerRequest $request, BudgetMouvement $mouvement): RedirectResponse
    {
        try {
            $this->cycle->payer(
                $mouvement,
                $request->user(),
                $request->validated()['mode_paiement'],
                $request->validated()['numero_piece'] ?? null,
                $request->validated()['compte_bancaire'] ?? null,
                isset($request->validated()['date_valeur'])
                    ? new \DateTimeImmutable($request->validated()['date_valeur'])
                    : null,
                $request->validated()['motif'] ?? null,
            );

            return back()->with('success', 'Paiement enregistré.');
        } catch (Throwable $e) {
            return back()->withErrors(['cycle' => $e->getMessage()]);
        }
    }
}
