<?php

namespace App\Reports\Expense;

use App\Models\ServiceDoneCertificate;
use App\Reports\Report;

class CertificatServiceFaitReport extends Report
{
    public function key(): string
    {
        return 'certificat_service_fait';
    }

    public function titre(): string
    {
        return 'Certificat de service fait';
    }

    public function description(): string
    {
        return "Constatation institutionnelle de l'exécution d'une prestation : description, montant constaté, conformité qualitative et quantitative.";
    }

    public function categorie(): string
    {
        return self::CAT_BUDGET;
    }

    public function template(): string
    {
        return 'reports.expense.certificat_service_fait';
    }

    public function icone(): string
    {
        return 'ClipboardCheck';
    }

    public function permission(): string
    {
        return 'expense.view';
    }

    public function filtres(): array
    {
        return [
            [
                'key' => 'certificat_id',
                'label' => 'Certificat',
                'type' => 'select',
                'required' => true,
                'options' => ServiceDoneCertificate::latest()->limit(100)->get(['id', 'reference'])
                    ->map(fn ($c) => ['value' => $c->id, 'label' => $c->reference])->toArray(),
            ],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $id = $filtres['certificat_id'] ?? null;
        if (! $id) {
            return ['certificat' => null];
        }

        $certificat = ServiceDoneCertificate::with([
            'mouvement:id,reference,montant,beneficiaire_nom',
            'mouvement.expenseRequest:id,numero,objet',
            'mouvement.supplier:id,code,libelle,nif,rccm',
            'constatePar:id,name,fonction',
            'validePar:id,name,fonction',
        ])->findOrFail($id);

        return ['certificat' => $certificat];
    }
}
