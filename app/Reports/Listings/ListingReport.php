<?php

namespace App\Reports\Listings;

use App\Reports\Report;
use Illuminate\Support\Collection;

/**
 * Base abstraite des rapports de type « liste tabulaire d'une entité ».
 *
 * Chaque sous-classe déclare :
 *   - les colonnes via columns()
 *   - le jeu de lignes via rows($filtres)
 *
 * Le rendu s'appuie sur un template Blade générique (reports.listings.generic).
 */
abstract class ListingReport extends Report
{
    /**
     * Colonnes du tableau.
     *
     * @return array<int, array{label: string, key: string, width?: string, align?: 'left'|'right'|'center', kind?: 'text'|'num'|'badge'|'progress'|'date'}>
     */
    abstract protected function columns(): array;

    /**
     * Jeu de lignes (chaque ligne est un tableau associatif clé → valeur).
     */
    abstract protected function rows(array $filtres): Collection;

    public function template(): string
    {
        return 'reports.listings.generic';
    }

    public function categorie(): string
    {
        return self::CAT_OPERATIONNEL;
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function icone(): string
    {
        return 'List';
    }

    public function donnees(array $filtres = []): array
    {
        $rows = $this->rows($filtres);

        return [
            'titreListe' => $this->titre(),
            'columns' => $this->columns(),
            'rows' => $rows,
            'count' => $rows->count(),
            'filtres_appliques' => $filtres,
        ];
    }

    /** Filtre helper : extrait une valeur du tableau filtres si non vide. */
    protected function filtre(array $filtres, string $key): mixed
    {
        $v = $filtres[$key] ?? null;

        return $v === '' ? null : $v;
    }
}
