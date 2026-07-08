<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CommunityChannel extends Model
{
    use HasFactory, SoftDeletes;

    public const VISIBILITY_MEMBERS = 'members';

    public const VISIBILITY_STAFF = 'staff';

    protected $fillable = [
        'discipline_id',
        'created_by',
        'name',
        'slug',
        'description',
        'visibility',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $channel) {
            if (blank($channel->slug)) {
                $channel->slug = Str::slug($channel->name.' '.Str::random(6));
            }
        });
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(CommunityMessage::class)->latest();
    }

    public function visibleMessages(): HasMany
    {
        return $this->messages()->where('status', CommunityMessage::STATUS_VISIBLE);
    }
}
