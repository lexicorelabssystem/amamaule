<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    protected function publisherUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('comunicaciones');

        return $user;
    }

    public function test_artist_can_create_activity(): void
    {
        $user = $this->artistUser();

        $response = $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'Concierto de prueba',
            'short_description' => 'Descripción corta',
            'description' => 'Descripción larga',
            'start_date' => now()->addWeek()->format('Y-m-d\TH:i'),
            'location' => 'Talca',
            'is_free' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', [
            'title' => 'Concierto de prueba',
            'artist_id' => $user->artist->id,
            'status' => Activity::STATUS_DRAFT,
        ]);
    }

    public function test_artist_can_update_own_activity(): void
    {
        $user = $this->artistUser();
        $activity = Activity::factory()->create(['artist_id' => $user->artist->id]);

        $response = $this->actingAs($user)->patch(route('activities.update', $activity), [
            'title' => 'Título actualizado',
            'short_description' => $activity->short_description,
            'description' => $activity->description,
            'is_free' => true,
        ]);

        $response->assertRedirect();
        $this->assertEquals('Título actualizado', $activity->fresh()->title);
    }

    public function test_publisher_can_publish_activity(): void
    {
        $publisher = $this->publisherUser();
        $artist = Artist::factory()->create();
        $activity = Activity::factory()->create(['artist_id' => $artist->id]);

        $response = $this->actingAs($publisher)
            ->patch(route('activities.publish', $activity));

        $response->assertRedirect();
        $this->assertEquals(Activity::STATUS_PUBLISHED, $activity->fresh()->status);
    }

    public function test_artist_can_upload_images_to_activity(): void
    {
        Storage::fake('local');

        $user = $this->artistUser();
        $activity = Activity::factory()->create(['artist_id' => $user->artist->id]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->actingAs($user)->post(route('activities.media.store', $activity), [
            'images' => [$file],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('media', [
            'mediable_type' => Activity::class,
            'mediable_id' => $activity->id,
        ]);

        $media = Media::where('mediable_type', Activity::class)
            ->where('mediable_id', $activity->id)
            ->first();

        $this->assertNotNull($media);
        Storage::disk('local')->assertExists($media->file_path);
        Storage::disk('local')->assertExists($media->thumbnail_path);
    }

    public function test_artist_can_set_cover_image(): void
    {
        Storage::fake('local');

        $user = $this->artistUser();
        $activity = Activity::factory()->create(['artist_id' => $user->artist->id]);

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $this->actingAs($user)->post(route('activities.media.store', $activity), ['images' => [$file]]);

        $media = Media::first();

        $response = $this->actingAs($user)
            ->patch(route('activities.media.cover', [$activity, $media]));

        $response->assertRedirect();
        $this->assertTrue($media->fresh()->is_cover);
        $this->assertEquals($media->id, $activity->fresh()->cover_media_id);
    }

    public function test_cleanup_command_removes_orphan_media_files(): void
    {
        Storage::fake('local');

        Storage::disk('local')->put('media/gallery/2026/07/orphan.jpg', 'content');
        Storage::disk('local')->put('media/gallery/2026/07/tracked.jpg', 'content');

        Media::factory()->create([
            'file_path' => 'media/gallery/2026/07/tracked.jpg',
            'thumbnail_path' => 'media/gallery/2026/07/tracked_thumb.jpg',
        ]);

        $this->artisan('media:cleanup')
            ->assertSuccessful();

        Storage::disk('local')->assertMissing('media/gallery/2026/07/orphan.jpg');
        Storage::disk('local')->assertExists('media/gallery/2026/07/tracked.jpg');
    }
}
