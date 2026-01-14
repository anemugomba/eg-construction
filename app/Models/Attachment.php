<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Attachment extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'uploaded_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * The parent model (polymorphic).
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The user who uploaded this attachment.
     */
    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the public URL for this attachment.
     * Note: Using public S3 bucket per spec decision.
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => config('filesystems.disks.s3.url') . '/' . $this->file_path
        );
    }

    /**
     * Check if this is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Get formatted file size.
     */
    protected function formattedFileSize(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->file_size) {
                    return null;
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $size = $this->file_size;
                $unitIndex = 0;

                while ($size >= 1024 && $unitIndex < count($units) - 1) {
                    $size /= 1024;
                    $unitIndex++;
                }

                return round($size, 2) . ' ' . $units[$unitIndex];
            }
        );
    }

    /**
     * Scope for images only.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    /**
     * Scope for a specific attachable.
     */
    public function scopeForAttachable(Builder $query, string $type, string $id): Builder
    {
        return $query->where('attachable_type', $type)
            ->where('attachable_id', $id);
    }
}
