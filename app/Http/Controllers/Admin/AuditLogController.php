<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = trim((string) $request->query('action', ''));
        $rawActorId = $request->query('actor_id');
        $actorId = is_numeric($rawActorId) ? (int) $rawActorId : null;
        $startDate = trim((string) $request->query('start_date', ''));
        $endDate = trim((string) $request->query('end_date', ''));

        $startAt = $startDate !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $startDate, 'Asia/Jakarta')
            : null;
        $endAt = $endDate !== ''
            ? CarbonImmutable::createFromFormat('Y-m-d', $endDate, 'Asia/Jakarta')
            : null;

        $logs = AuditLog::query()
            ->with(['actor:id,name', 'authorizer:id,name'])
            ->when($action !== '', fn ($q) => $q->where('action', $action))
            ->when($actorId !== null, fn ($q) => $q->where(fn ($sub) => $sub->where('actor_id', $actorId)->orWhere('authorized_by', $actorId)))
            ->when($startAt instanceof CarbonImmutable, fn ($q) => $q->where('created_at', '>=', $startAt->startOfDay()))
            ->when($endAt instanceof CarbonImmutable, fn ($q) => $q->where('created_at', '<=', $endAt->endOfDay()))
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor?->name,
                'authorizer_name' => $log->authorizer?->name,
                'auditable_type' => $log->auditable_type !== null ? class_basename($log->auditable_type) : null,
                'auditable_id' => $log->auditable_id,
                'reason' => $log->reason,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        $actions = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $staffList = User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u): array => ['id' => $u->id, 'name' => $u->name]);

        return view('admin.audit-logs.index', [
            'logs' => $logs->values()->all(),
            'actions' => $actions->values()->all(),
            'staffList' => $staffList->values()->all(),
            'filters' => [
                'action' => $action,
                'actor_id' => $actorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
