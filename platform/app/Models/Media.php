<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public static array $statuses = [
        self::STATUS_QUEUED,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_FAILED,
    ];

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'collection_name',
        'file_name',
        'file_path',
        'thumbnail_path',
        'mime_type',
        'size',
        'order',
        'is_cover',
        'custom_properties',
        'status',
        'pending_path',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'order' => 'integer',
            'size' => 'integer',
            'custom_properties' => 'array',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isQueued(): bool
    {
        return $this->status === self::STATUS_QUEUED;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function coverFor(): BelongsTo
    {
        return $this->hasOne(Activity::class, 'cover_media_id');
    }

    public function fullUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('public')->url($this->thumbnail_path) : null;
    }

    public function deleteFiles(): void
    {
        Storage::disk('public')->delete(array_filter([
            $this->file_path,
            $this->thumbnail_path,
            $this->pending_path,
        ]));
    }

    public function deletePendingFile(): void
    {
        if ($this->pending_path) {
            Storage::disk('public')->delete($this->pending_path);
        }
    }

    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            $media->deleteFiles();
        });
    }
}
