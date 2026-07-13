<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaUploadJob;
use App\Models\Activity;
use App\Models\Artist;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function artistUserWithActivity(): array
    {
        $user = User::factory()->create();
        $user->assignRole('artista');
        $artist = Artist::factory()->create(['user_id' => $user->id]);
        $activity = Activity::factory()->create(['artist_id' => $artist->id]);

        return [$user, $activity];
    }

    public function test_artist_can_upload_images_to_own_activity_gallery(): void
    {
        Storage::fake('public');
        [$user, $activity] = $this->artistUserWithActivity();

        $this->actingAs($user)
            ->post(route('activities.media.store', $activity), [
                'images' => [
                    UploadedFile::fake()->image('one.jpg', 400, 400),
                    UploadedFile::fake()->image('two.jpg', 400, 400),
                ],
            ])
            ->assertRedirect(route('activities.edit', $activity));

        $activity->refresh();
        $this->assertCount(2, $activity->media);

        foreach ($activity->media as $media) {
            $this->assertTrue($media->isCompleted());
            Storage::disk('public')->assertExists($media->file_path);
        }
    }

    public function test_upload_dispatches_processing_job_and_starts_queued(): void
    {
        Storage::fake('public');
        Queue::fake();
        [$user, $activity] = $this->artistUserWithActivity();

        $this->actingAs($user)
            ->post(route('activities.media.store', $activity), [
                'images' => [UploadedFile::fake()->image('one.jpg', 400, 400)],
            ])
            ->assertRedirect();

        $media = $activity->media()->first();

        $this->assertTrue($media->isQueued());
        $this->assertNotNull($media->pending_path);
        Queue::assertPushed(ProcessMediaUploadJob::class, fn ($job) => $job->media->id === $media->id);
    }

    public function test_artist_cannot_upload_to_another_artists_activity(): void
    {
        [, $activity] = $this->artistUserWithActivity();
        [$other] = $this->artistUserWithActivity();

        $this->actingAs($other)
            ->post(route('activities.media.store', $activity), [
                'images' => [UploadedFile::fake()->image('one.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_upload_rejects_non_image_files(): void
    {
        [$user, $activity] = $this->artistUserWithActivity();

        $this->actingAs($user)
            ->post(route('activities.media.store', $activity), [
                'images' => [UploadedFile::fake()->create('document.pdf', 100)],
            ])
            ->assertSessionHasErrors('images.0');
    }
}
