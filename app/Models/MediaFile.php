<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    protected $fillable = [
        'file_name', 'file_path', 'disk', 'file_type',
        'mime_type', 'file_size', 'alt_text', 'title', 'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }

    // ── Accessors ─────────────────────────────────────────────────────────
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk ?? 'public')->url($this->file_path);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)        return "{$bytes} B";
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}
