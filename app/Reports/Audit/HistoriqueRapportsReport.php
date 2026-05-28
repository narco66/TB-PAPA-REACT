<?php

namespace App\Reports\Audit;

use App\Models\GeneratedReport;
use App\Reports\Report;

class HistoriqueRapportsReport extends Report
{
    public function key(): string
    {
        return 'historique_rapports';
    }

    public function titre(): string
    {
        return 'Registre des documents PDF générés';
    }

    public function description(): string
    {
        return 'Inventaire institutionnel des rapports PDF produits par le système, avec auteur, hash et code de vérification. Document opposable.';
    }

    public function categorie(): string
    {
        return self::CAT_AUDIT;
    }

    public function template(): string
    {
        return 'reports.audit.historique_rapports';
    }

    public function icone(): string
    {
        return 'FileText';
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filtres(): array
    {
        return [
            ['key' => 'q', 'label' => 'Recherche (titre)', 'type' => 'text'],
            [
                'key' => 'categorie',
                'label' => 'Catégorie',
                'type' => 'select',
                'options' => array_merge(
                    [['value' => '', 'label' => 'Toutes catégories']],
                    array_map(fn ($k, $v) => ['value' => $k, 'label' => $v],
                        array_keys(self::CATEGORIES), array_values(self::CATEGORIES)),
                ),
            ],
            ['key' => 'date_debut', 'label' => 'Du', 'type' => 'date'],
            ['key' => 'date_fin', 'label' => 'Au', 'type' => 'date'],
            ['key' => 'limit', 'label' => 'Limite (lignes)', 'type' => 'number', 'default' => 500],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $query = GeneratedReport::query()
            ->with('genereur:id,name,matricule,email')
            ->orderByDesc('genere_at');

        if (! empty($filtres['categorie'])) {
            $query->where('categorie', $filtres['categorie']);
        }
        if (! empty($filtres['q'])) {
            $query->where('titre', 'like', '%' . $filtres['q'] . '%');
        }
        if (! empty($filtres['date_debut'])) {
            $query->whereDate('genere_at', '>=', $filtres['date_debut']);
        }
        if (! empty($filtres['date_fin'])) {
            $query->whereDate('genere_at', '<=', $filtres['date_fin']);
        }

        $limit = (int) ($filtres['limit'] ?? 500);
        $rapports = $query->limit(max(10, min(2000, $limit)))->get();

        // Statistiques
        $stats = [
            'total' => $rapports->count(),
            'taille_totale_octets' => (int) $rapports->sum('taille_octets'),
            'nb_telechargements' => (int) $rapports->sum('nb_telechargements'),
            'auteurs_uniques' => $rapports->pluck('genere_par_id')->unique()->filter()->count(),
            'periode' => [
                'debut' => $rapports->min('genere_at'),
                'fin' => $rapports->max('genere_at'),
            ],
        ];

        // Répartition par catégorie
        $parCategorie = $rapports->groupBy('categorie')->map->count()->sortDesc();

        return compact('rapports', 'stats', 'parCategorie', 'filtres');
    }
}
