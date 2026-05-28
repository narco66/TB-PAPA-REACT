<?php

namespace App\Reports\Expense;

use App\Models\Reception;
use App\Reports\Report;

class PvReceptionReport extends Report
{
    public function key(): string
    {
        return 'pv_reception';
    }

    public function titre(): string
    {
        return 'Procès-verbal de réception';
    }

    public function description(): string
    {
        return 'PV de réception (biens, services, travaux) avec commission de réception, conformité et réserves éventuelles.';
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.pv_reception';
    }

    public function icone(): string
    {
        return 'FileCheck';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'reception_id',
                'label' => 'Réception',
                'type' => 'select',
                'required' => true,
                'options' => Reception::latest()->limit(100)->get(['id', 'reference'])
                    ->map(fn ($r) => ['value' => $r->id, 'label' => $r->reference])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['reception_id'] ?? null;
        if (! $id) {
            return ['reception' => null];
        }

        $reception = Reception::with([
            'mouvement:id,reference,montant',
            'mouvement.expenseRequest:id,numero,objet',
            'mouvement.supplier:id,code,libelle,nif,rccm,adresse',
            'serviceDoneCertificate:id,reference,montant_constate',
            'presidentCommission:id,name,fonction',
            'membre1:id,name,fonction',
            'membre2:id,name,fonction',
            'saisiPar:id,name',
            'validePar:id,name,fonction',
        ])->findOrFail($id);

        return ['reception' => $reception];
    }
}
