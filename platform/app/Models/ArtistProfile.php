<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtistProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'artist_id',
        'experience',
        'education',
        'awards',
        'portfolio_url',
        'video_url',
        'availability',
        'representation',
        'press_links',
        'tech_rider',
        'stage_requirements',
    ];

    protected function casts(): array
    {
        return [
            'press_links' => 'array',
        ];
    }

    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }
}
