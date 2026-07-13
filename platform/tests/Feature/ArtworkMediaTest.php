<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaUploadJob;
use App\Models\Artist;
use App\Models\Artwork;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtworkMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function artistUserWithArtwork(): array
    {
        $user = User::factory()->create();
        $user->assignRole('artista');
        $artist = Artist::factory()->create(['user_id' => $user->id]);
        $artwork = Artwork::factory()->create(['artist_id' => $artist->id]);

        return [$user, $artwork];
    }

    public function test_artist_can_upload_images_to_own_artwork_gallery(): void
    {
        Storage::fake('public');
        [$user, $artwork] = $this->artistUserWithArtwork();

        $this->actingAs($user)
            ->post(route('artworks.media.store', $artwork), [
                'images' => [
                    UploadedFile::fake()->image('one.jpg', 400, 400),
                    UploadedFile::fake()->image('two.jpg', 400, 400),
                ],
            ])
            ->assertRedirect(route('artworks.edit', $artwork));

        $artwork->refresh();
        $this->assertCount(2, $artwork->media);

        foreach ($artwork->media as $media) {
            $this->assertTrue($media->isCompleted());
            Storage::disk('public')->assertExists($media->file_path);
        }
    }

    public function test_upload_dispatches_processing_job_and_starts_queued(): void
    {
        Storage::fake('public');
        Queue::fake();
        [$user, $artwork] = $this->artistUserWithArtwork();

        $this->actingAs($user)
            ->post(route('artworks.media.store', $artwork), [
                'images' => [UploadedFile::fake()->image('one.jpg', 400, 400)],
            ])
            ->assertRedirect();

        $media = $artwork->media()->first();

        $this->assertTrue($media->isQueued());
        $this->assertNotNull($media->pending_path);
        Queue::assertPushed(ProcessMediaUploadJob::class, fn ($job) => $job->media->id === $media->id);
    }

    public function test_artist_cannot_upload_to_another_artists_artwork(): void
    {
        [, $artwork] = $this->artistUserWithArtwork();
        [$other] = $this->artistUserWithArtwork();

        $this->actingAs($other)
            ->post(route('artworks.media.store', $artwork), [
                'images' => [UploadedFile::fake()->image('one.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_upload_rejects_non_image_files(): void
    {
        [$user, $artwork] = $this->artistUserWithArtwork();

        $this->actingAs($user)
            ->post(route('artworks.media.store', $artwork), [
                'images' => [UploadedFile::fake()->create('document.pdf', 100)],
            ])
            ->assertSessionHasErrors('images.0');
    }
}
