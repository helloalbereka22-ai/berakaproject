<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log a business operation with full context.
     */
    public static function log(
        string $action,
        string $auditableType,
        int|string|null $auditableId = null,
        array $beforeData = [],
        array $afterData = [],
        ?int $businessId = null,
        ?int $branchId = null,
        ?string $description = null
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'user_id'        => $user?->id,
            'action'         => $action,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'before_data'    => $beforeData ?: null,
            'after_data'     => $afterData  ?: null,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'business_id'    => $businessId,
            'branch_id'      => $branchId,
            'description'    => $description,
            'created_at'     => now(),
        ]);
    }

    // ── Convenience shorthands ──────────────────────────────

    public static function logCreate(
        string $auditableType,
        $model,
        ?int $businessId = null,
        ?int $branchId = null
    ): void {
        static::log('created', $auditableType, $model->id, [], $model->toArray(), $businessId, $branchId);
    }

    public static function logUpdate(
        string $auditableType,
        $model,
        array $original,
        ?int $businessId = null,
        ?int $branchId = null
    ): void {
        static::log('updated', $auditableType, $model->id, $original, $model->toArray(), $businessId, $branchId);
    }

    public static function logDelete(
        string $auditableType,
        $model,
        ?int $businessId = null,
        ?int $branchId = null
    ): void {
        static::log('deleted', $auditableType, $model->id, $model->toArray(), [], $businessId, $branchId);
    }

    public static function logLogin(?int $userId): void
    {
        AuditLog::create([
            'user_id'        => $userId,
            'action'         => 'login',
            'auditable_type' => 'App\\Models\\User',
            'auditable_id'   => $userId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'description'    => 'User logged in',
            'created_at'     => now(),
        ]);
    }

    public static function logLogout(?int $userId): void
    {
        AuditLog::create([
            'user_id'        => $userId,
            'action'         => 'logout',
            'auditable_type' => 'App\\Models\\User',
            'auditable_id'   => $userId,
            'ip_address'     => request()->ip(),
            'user_agent'     => request()->userAgent(),
            'description'    => 'User logged out',
            'created_at'     => now(),
        ]);
    }
}
