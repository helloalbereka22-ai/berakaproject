<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Migration uses created_at only (no updated_at)
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before_data',
        'after_data',
        'ip_address',
        'user_agent',
        'business_id',
        'branch_id',
        'description',
        'created_at',
    ];

    protected $casts = [
        'before_data' => 'array',
        'after_data'  => 'array',
        'created_at'  => 'datetime',
    ];

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    /**
     * Polymorphic auditable subject (the model being audited).
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Human-readable action badge colour.
     */
    public function actionColor(): string
    {
        return match ($this->action) {
            'created'  => 'emerald',
            'updated'  => 'blue',
            'deleted'  => 'red',
            'approved' => 'violet',
            'rejected' => 'amber',
            'login'    => 'sky',
            'logout'   => 'slate',
            default    => 'slate',
        };
    }
}
