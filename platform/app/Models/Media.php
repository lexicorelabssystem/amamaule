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

    public function coverFor(): BelongsTo
    {
        return $this->hasOne(Activity::class, 'cover_media_id');
    }

    public function fullUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    public function deleteFiles(): void
    {
        Storage::delete([$this->file_path, $this->thumbnail_path]);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $media) {
            $media->deleteFiles();
        });
    }
}
