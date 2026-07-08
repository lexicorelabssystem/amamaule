<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WordPressPublication extends Model
{
    protected $table = 'wordpress_publications';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DRAFT = 'draft';

    protected $fillable = ['publishable_type', 'publishable_id', 'wordpress_post_id', 'wordpress_post_type', 'status', 'content_hash', 'wordpress_url', 'last_error', 'attempts', 'published_at', 'synced_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'synced_at' => 'datetime', 'attempts' => 'integer'];
    }

    public function publishable(): MorphTo
    {
        return $this->morphTo();
    }
}
