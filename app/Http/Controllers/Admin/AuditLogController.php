<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'module' => $request->string('module')->toString(),
            'action' => $request->string('action')->toString(),
        ];

        $logs = AuditLog::query()
            ->when($filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('actor_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['module'] !== '', fn ($q) => $q->where('module', $filters['module']))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (AuditLog $log) => [
                'id' => $log->id,
                'actor_name' => $log->actor_name ?: 'Sistem',
                'action' => $log->action,
                'module' => $log->module,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $filters,
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => [
                'id' => $auditLog->id,
                'actor_name' => $auditLog->actor_name ?: 'Sistem',
                'actor_user_id' => $auditLog->actor_user_id,
                'action' => $auditLog->action,
                'module' => $auditLog->module,
                'description' => $auditLog->description,
                'auditable_type' => $auditLog->auditable_type,
                'auditable_id' => $auditLog->auditable_id,
                'old_values' => $auditLog->old_values,
                'new_values' => $auditLog->new_values,
                'ip_address' => $auditLog->ip_address,
                'user_agent' => $auditLog->user_agent,
                'created_at' => $auditLog->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            ],
        ]);
    }
}
