<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): Response
    {
        $request->user()->can('audit.viewLog') || abort(403);

        $query = Activity::query()->with(['causer:id,name,email,matricule', 'subject'])
            ->orderByDesc('id');

        if ($q = $request->string('q')->trim()->toString()) {
            $query->where(fn ($w) => $w
                ->where('description', 'like', "%{$q}%")
                ->orWhere('log_name', 'like', "%{$q}%"),
            );
        }
        if ($event = $request->string('event')->toString()) {
            $query->where('event', $event);
        }
        if ($logName = $request->string('log_name')->toString()) {
            $query->where('log_name', $logName);
        }
        if ($causerId = $request->integer('causer_id')) {
            $query->where('causer_id', $causerId);
        }

        $logs = $query->paginate(50)->withQueryString();

        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'log_name' => $log->log_name,
                'description' => $log->description,
                'event' => $log->event,
                'subject_type' => $log->subject_type ? class_basename($log->subject_type) : null,
                'subject_id' => $log->subject_id,
                'causer' => $log->causer ? [
                    'id' => $log->causer->id,
                    'name' => $log->causer->name,
                    'matricule' => $log->causer->matricule ?? null,
                ] : null,
                'properties' => $log->properties->toArray(),
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        return Inertia::render('admin/audit/index', [
            'logs' => $logs,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'event' => $request->string('event')->toString(),
                'log_name' => $request->string('log_name')->toString(),
            ],
            'evenements' => ['created', 'updated', 'deleted', 'restored'],
        ]);
    }
}
