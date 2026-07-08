<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\WordPressPublication;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class WordPressPublicationService
{
    public function __construct(private readonly WordPressPublisher $publisher) {}

    public function publishArtist(Artist $artist): WordPressPublication
    {
        abort_unless($artist->isApproved(), 422, 'Solo artistas aprobados pueden publicarse en WordPress.');

        $artist->loadMissing(['territory', 'mainDiscipline', 'disciplines']);

        return $this->publish($artist, $this->artistPayload($artist));
    }

    public function publishActivity(Activity $activity): WordPressPublication
    {
        abort_unless($activity->isPublished(), 422, 'Solo actividades publicadas pueden sincronizarse con WordPress.');

        $activity->loadMissing(['artist', 'territory']);

        return $this->publish($activity, $this->activityPayload($activity));
    }

    public function unpublish(Model $publishable): WordPressPublication
    {
        $publication = $publishable->wordpressPublication;
        abort_unless($publication?->wordpress_post_id, 422, 'No existe una publicaci?n WordPress asociada.');

        try {
            $response = $this->publisher->updatePost(
                (int) $publication->wordpress_post_id,
                ['status' => 'draft'],
                $publication->wordpress_post_type
            );

            $publication->update([
                'status' => WordPressPublication::STATUS_DRAFT,
                'wordpress_url' => $response['link'] ?? $publication->wordpress_url,
                'last_error' => null,
                'synced_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($publication, $exception);
        }

        return $publication->fresh();
    }

    private function publish(Model $publishable, array $payload): WordPressPublication
    {
        $publication = $publishable->wordpressPublication()->firstOrCreate([], [
            'status' => WordPressPublication::STATUS_PENDING,
            'wordpress_post_type' => 'posts',
        ]);

        $publication->increment('attempts');

        try {
            $response = $publication->wordpress_post_id
                ? $this->publisher->updatePost((int) $publication->wordpress_post_id, $payload, $publication->wordpress_post_type)
                : $this->publisher->createPost($payload, $publication->wordpress_post_type);

            $publication->update([
                'wordpress_post_id' => $response['id'] ?? $publication->wordpress_post_id,
                'status' => WordPressPublication::STATUS_PUBLISHED,
                'content_hash' => hash('sha256', json_encode($payload)),
                'wordpress_url' => $response['link'] ?? $publication->wordpress_url,
                'last_error' => null,
                'published_at' => $publication->published_at ?? now(),
                'synced_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($publication, $exception);
        }

        return $publication->fresh();
    }

    private function markFailed(WordPressPublication $publication, Throwable $exception): void
    {
        $publication->update([
            'status' => WordPressPublication::STATUS_FAILED,
            'last_error' => Str::limit($exception->getMessage(), 1000),
            'synced_at' => now(),
        ]);
    }

    private function artistPayload(Artist $artist): array
    {
        return [
            'title' => $artist->displayName(),
            'slug' => $artist->slug,
            'status' => 'publish',
            'content' => view('wordpress.artist', compact('artist'))->render(),
            'excerpt' => $artist->bio_short,
            'meta' => [
                'ama_platform_type' => 'artist',
                'ama_platform_id' => $artist->id,
            ],
        ];
    }

    private function activityPayload(Activity $activity): array
    {
        return [
            'title' => $activity->title,
            'slug' => $activity->slug,
            'status' => 'publish',
            'content' => view('wordpress.activity', compact('activity'))->render(),
            'excerpt' => $activity->short_description,
            'meta' => [
                'ama_platform_type' => 'activity',
                'ama_platform_id' => $activity->id,
            ],
        ];
    }
}
