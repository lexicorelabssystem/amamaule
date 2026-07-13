<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function artistUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('artista');
        Artist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    protected function mediaWithStatus(Artist $artist, string $status, array $overrides = []): Media
    {
        return Media::factory()->for($artist, 'mediable')->create(array_merge([
            'collection_name' => 'avatar',
            'file_path' => null,
            'thumbnail_path' => null,
            'status' => $status,
        ], $overrides));
    }

    public function test_owner_can_view_status_of_queued_media(): void
    {
        $user = $this->artistUser();
        $media = $this->mediaWithStatus($user->artist, Media::STATUS_QUEUED, [
            'pending_path' => 'media-pending/avatar/x.jpg',
        ]);

        $this->actingAs($user)
            ->getJson(route('media.status', $media))
            ->assertOk()
            ->assertJson([
                'id' => $media->id,
                'status' => 'queued',
                'error_message' => null,
                'url' => null,
                'thumbnail_url' => null,
            ]);
    }

    public function test_owner_can_view_status_of_processing_media(): void
    {
        $user = $this->artistUser();
        $media = $this->mediaWithStatus($user->artist, Media::STATUS_PROCESSING, [
            'pending_path' => 'media-pending/avatar/x.jpg',
        ]);

        $this->actingAs($user)
            ->getJson(route('media.status', $media))
            ->assertOk()
            ->assertJson(['status' => 'processing']);
    }

    public function test_owner_can_view_status_of_completed_media(): void
    {
        $user = $this->artistUser();
        $media = $this->mediaWithStatus($user->artist, Media::STATUS_COMPLETED, [
            'file_path' => 'media/avatar/2026/07/completed-test.jpg',
            'thumbnail_path' => 'media/avatar/2026/07/completed-test_thumb.jpg',
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('media.status', $media))
            ->assertOk()
            ->assertJson(['status' => 'completed']);

        $response->assertJsonPath('url', fn ($url) => ! empty($url));
        $response->assertJsonPath('thumbnail_url', fn ($url) => ! empty($url));
    }

    public function test_owner_can_view_status_of_failed_media(): void
    {
        $user = $this->artistUser();
        $media = $this->mediaWithStatus($user->artist, Media::STATUS_FAILED, [
            'error_message' => 'Unable to decode input',
        ]);

        $this->actingAs($user)
            ->getJson(route('media.status', $media))
            ->assertOk()
            ->assertJson([
                'status' => 'failed',
                'error_message' => 'Unable to decode input',
            ]);
    }

    public function test_other_artist_cannot_view_status_of_media_they_do_not_own(): void
    {
        $owner = $this->artistUser();
        $other = $this->artistUser();
        $media = $this->mediaWithStatus($owner->artist, Media::STATUS_COMPLETED);

        $this->actingAs($other)
            ->getJson(route('media.status', $media))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $user = $this->artistUser();
        $media = $this->mediaWithStatus($user->artist, Media::STATUS_COMPLETED);

        $this->get(route('media.status', $media))
            ->assertRedirect(route('login'));
    }
}
