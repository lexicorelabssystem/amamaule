<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    public const STATUS_SKIPPED = 'skipped';

    public static array $statuses = [
        self::STATUS_PENDING,
        self::STATUS_SUCCESS,
        self::STATUS_ERROR,
        self::STATUS_SKIPPED,
    ];

    protected $fillable = [
        'import_id',
        'row_number',
        'raw_data',
        'status',
        'errors',
        'artist_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'errors' => 'array',
            'row_number' => 'integer',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markSuccess(?Artist $artist = null, ?User $user = null): void
    {
        $this->status = self::STATUS_SUCCESS;
        $this->errors = null;
        $this->artist_id = $artist?->id;
        $this->user_id = $user?->id;
        $this->save();
    }

    public function markError(array $errors): void
    {
        $this->status = self::STATUS_ERROR;
        $this->errors = $errors;
        $this->save();
    }

    public function markSkipped(?array $reason = null): void
    {
        $this->status = self::STATUS_SKIPPED;
        $this->errors = $reason;
        $this->save();
    }
}
