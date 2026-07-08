<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Artist extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_NEEDS_CHANGES = 'needs_changes';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    public static array $statuses = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_IN_REVIEW,
        self::STATUS_NEEDS_CHANGES,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'user_id',
        'legal_name',
        'public_name',
        'artistic_name',
        'slug',
        'document_number',
        'email_contact',
        'phone',
        'website',
        'region',
        'province',
        'commune',
        'address',
        'territory_id',
        'main_discipline_id',
        'bio_short',
        'bio_long',
        'social_networks',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'profile_views',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'social_networks' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'profile_views' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $artist) {
            if (empty($artist->slug)) {
                $base = $artist->public_name
                    ?? $artist->artistic_name
                    ?? $artist->legal_name
                    ?? 'artista';
                $artist->slug = Str::slug($base . ' ' . Str::random(6));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(Territory::class);
    }

    public function mainDiscipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class, 'main_discipline_id');
    }

    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(Discipline::class, 'artist_discipline')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePendingReview($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_IN_REVIEW]);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function displayName(): string
    {
        return $this->public_name
            ?? $this->artistic_name
            ?? $this->legal_name
            ?? 'Artista sin nombre';
    }
}
