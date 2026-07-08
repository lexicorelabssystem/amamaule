<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityMessage extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_VISIBLE = 'visible';

    public const STATUS_HIDDEN = 'hidden';

    protected $fillable = [
        'community_channel_id',
        'user_id',
        'body',
        'status',
        'hidden_at',
        'hidden_by',
    ];

    protected function casts(): array
    {
        return ['hidden_at' => 'datetime'];
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(CommunityChannel::class, 'community_channel_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(ModerationReport::class, 'reportable');
    }

    public function hide(User $moderator): void
    {
        $this->update([
            'status' => self::STATUS_HIDDEN,
            'hidden_at' => now(),
            'hidden_by' => $moderator->id,
        ]);
    }
}
