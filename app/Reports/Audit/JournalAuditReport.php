<?php

namespace App\Reports\Audit;

use App\Reports\Report;
use Spatie\Activitylog\Models\Activity;

class JournalAuditReport extends Report
{
    public function key(): string
    {
        return 'journal_audit';
    }

    public function titre(): string
    {
        return 'Journal d\'audit institutionnel';
    }

    public function description(): string
    {
        return 'Extrait du journal d\'audit système : créations, modifications, validations, suppressions. Conforme ISO 27001.';
    }

    public function categorie(): string
    {
        return self::CAT_AUDIT;
    }

    public function template(): string
    {
        return 'reports.audit.journal';
    }

    public function icone(): string
    {
        return 'ShieldCheck';
    }

    public function orientation(): string
    {
        return 'landscape';
    }

    public function filtres(): array
    {
        return [
            ['key' => 'date_debut', 'label' => 'Du', 'type' => 'date'],
            ['key' => 'date_fin', 'label' => 'Au', 'type' => 'date'],
            ['key' => 'event', 'label' => 'Type d\'événement', 'type' => 'select', 'options' => [
                ['value' => '', 'label' => 'Tous'],
                ['value' => 'created', 'label' => 'Création'],
                ['value' => 'updated', 'label' => 'Modification'],
                ['value' => 'deleted', 'label' => 'Suppression'],
            ]],
            ['key' => 'limit', 'label' => 'Limite de lignes', 'type' => 'number', 'default' => 200],
        ];
    }

    public function donnees(array $filtres = []): array
    {
        $query = Activity::query()->with('causer:id,name,email,matricule')->orderByDesc('id');

        if (! empty($filtres['date_debut'])) {
            $query->whereDate('created_at', '>=', $filtres['date_debut']);
        }
        if (! empty($filtres['date_fin'])) {
            $query->whereDate('created_at', '<=', $filtres['date_fin']);
        }
        if (! empty($filtres['event'])) {
            $query->where('event', $filtres['event']);
        }

        $limit = (int) ($filtres['limit'] ?? 200);
        $logs = $query->limit($limit)->get();

        $stats = [
            'total' => $logs->count(),
            'created' => $logs->where('event', 'created')->count(),
            'updated' => $logs->where('event', 'updated')->count(),
            'deleted' => $logs->where('event', 'deleted')->count(),
            'auteurs_uniques' => $logs->pluck('causer_id')->unique()->filter()->count(),
        ];

        return compact('logs', 'stats', 'filtres');
    }
}
