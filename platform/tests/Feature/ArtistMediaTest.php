<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaUploadJob;
use App\Models\Artist;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtistMediaTest extends TestCase
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

    public function test_artist_can_upload_own_avatar(): void
    {
        Storage::fake('public');
        $user = $this->artistUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('artists.avatar', $user->artist), ['avatar' => $file])
            ->assertRedirect();

        $artist = $user->artist->fresh();
        $this->assertNotNull($artist->avatar_media_id);
        Storage::disk('public')->assertExists($artist->avatar->file_path);
    }

    public function test_artist_can_upload_own_cover(): void
    {
        Storage::fake('public');
        $user = $this->artistUser();
        $file = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $this->actingAs($user)
            ->post(route('artists.cover', $user->artist), ['cover' => $file])
            ->assertRedirect();

        $artist = $user->artist->fresh();
        $this->assertNotNull($artist->cover_media_id);
        Storage::disk('public')->assertExists($artist->cover->file_path);
    }

    public function test_uploading_a_new_avatar_replaces_and_removes_the_old_one(): void
    {
        Storage::fake('public');
        $user = $this->artistUser();

        $this->actingAs($user)->post(route('artists.avatar', $user->artist), [
            'avatar' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $firstMediaId = $user->artist->fresh()->avatar_media_id;
        $firstPath = Media::find($firstMediaId)->file_path;

        $this->actingAs($user)->post(route('artists.avatar', $user->artist), [
            'avatar' => UploadedFile::fake()->image('second.jpg'),
        ]);
        $artist = $user->artist->fresh();

        $this->assertNotEquals($firstMediaId, $artist->avatar_media_id);
        $this->assertDatabaseMissing('media', ['id' => $firstMediaId]);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($artist->avatar->file_path);
    }

    public function test_artist_cannot_upload_avatar_to_another_artists_profile(): void
    {
        $owner = $this->artistUser();
        $other = $this->artistUser();

        $this->actingAs($other)
            ->post(route('artists.avatar', $owner->artist), ['avatar' => UploadedFile::fake()->image('avatar.jpg')])
            ->assertForbidden();
    }

    public function test_avatar_upload_rejects_non_image_files(): void
    {
        $user = $this->artistUser();

        $this->actingAs($user)
            ->post(route('artists.avatar', $user->artist), [
                'avatar' => UploadedFile::fake()->create('document.pdf', 100),
            ])
            ->assertSessionHasErrors('avatar');
    }

    public function test_avatar_upload_dispatches_processing_job_and_starts_queued(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = $this->artistUser();
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $this->actingAs($user)
            ->post(route('artists.avatar', $user->artist), ['avatar' => $file])
            ->assertRedirect();

        $artist = $user->artist->fresh();
        $media = Media::find($artist->avatar_media_id);

        $this->assertTrue($media->isQueued());
        $this->assertNull($media->file_path);
        $this->assertNotNull($media->pending_path);
        Storage::disk('public')->assertExists($media->pending_path);

        Queue::assertPushed(ProcessMediaUploadJob::class, fn ($job) => $job->media->id === $media->id);
    }

    public function test_cover_upload_dispatches_processing_job_and_starts_queued(): void
    {
        Storage::fake('public');
        Queue::fake();
        $user = $this->artistUser();
        $file = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $this->actingAs($user)
            ->post(route('artists.cover', $user->artist), ['cover' => $file])
            ->assertRedirect();

        $artist = $user->artist->fresh();
        $media = Media::find($artist->cover_media_id);

        $this->assertTrue($media->isQueued());
        $this->assertNull($media->file_path);
        $this->assertNotNull($media->pending_path);
        Storage::disk('public')->assertExists($media->pending_path);

        Queue::assertPushed(ProcessMediaUploadJob::class, fn ($job) => $job->media->id === $media->id);
    }
}
