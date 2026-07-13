<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaUploadJob;
use App\Models\Activity;
use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProcessMediaUploadJobTest extends TestCase
{
    use RefreshDatabase;

    protected function queuedMedia(string $pendingPath, array $overrides = []): Media
    {
        $activity = Activity::factory()->create();

        return Media::factory()->for($activity, 'mediable')->create(array_merge([
            'collection_name' => 'gallery',
            'file_path' => null,
            'thumbnail_path' => null,
            'status' => Media::STATUS_QUEUED,
            'pending_path' => $pendingPath,
        ], $overrides));
    }

    protected function fakeJpegContents(): string
    {
        $image = imagecreatetruecolor(500, 500);
        imagefill($image, 0, 0, imagecolorallocate($image, 100, 150, 220));
        ob_start();
        imagejpeg($image);
        $contents = ob_get_clean();
        imagedestroy($image);

        return $contents;
    }

    public function test_job_processes_valid_pending_image_and_marks_completed(): void
    {
        Storage::fake('public');
        $pendingPath = 'media-pending/gallery/test.jpg';
        Storage::disk('public')->put($pendingPath, $this->fakeJpegContents());

        $media = $this->queuedMedia($pendingPath);

        ProcessMediaUploadJob::dispatchSync($media);
        $media->refresh();

        $this->assertTrue($media->isCompleted());
        $this->assertNotNull($media->file_path);
        $this->assertNotNull($media->thumbnail_path);
        $this->assertNull($media->pending_path);
        $this->assertNull($media->error_message);
        Storage::disk('public')->assertExists($media->file_path);
        Storage::disk('public')->assertExists($media->thumbnail_path);
        Storage::disk('public')->assertMissing($pendingPath);
    }

    public function test_job_marks_failed_when_pending_file_is_not_a_valid_image(): void
    {
        Storage::fake('public');
        $pendingPath = 'media-pending/gallery/corrupt.jpg';
        Storage::disk('public')->put($pendingPath, 'esto no es una imagen valida');

        $media = $this->queuedMedia($pendingPath);

        ProcessMediaUploadJob::dispatchSync($media);
        $media->refresh();

        $this->assertTrue($media->isFailed());
        $this->assertNull($media->file_path);
        $this->assertNull($media->pending_path);
        $this->assertNotNull($media->error_message);
        Storage::disk('public')->assertMissing($pendingPath);
    }

    public function test_job_clears_previous_cover_when_new_cover_completes(): void
    {
        Storage::fake('public');
        $activity = Activity::factory()->create();

        $oldCover = Media::factory()->for($activity, 'mediable')->create([
            'collection_name' => 'gallery',
            'is_cover' => true,
        ]);

        $pendingPath = 'media-pending/gallery/new-cover.jpg';
        Storage::disk('public')->put($pendingPath, $this->fakeJpegContents());

        $newCover = Media::factory()->for($activity, 'mediable')->create([
            'collection_name' => 'gallery',
            'file_path' => null,
            'thumbnail_path' => null,
            'status' => Media::STATUS_QUEUED,
            'pending_path' => $pendingPath,
            'is_cover' => true,
        ]);

        ProcessMediaUploadJob::dispatchSync($newCover);

        $this->assertFalse($oldCover->fresh()->is_cover);
        $this->assertTrue($newCover->fresh()->is_cover);
    }

    public function test_job_failed_hook_marks_media_as_failed(): void
    {
        Storage::fake('public');
        $media = $this->queuedMedia('media-pending/gallery/never-created.jpg');

        (new ProcessMediaUploadJob($media))->failed(new RuntimeException('timeout simulado'));
        $media->refresh();

        $this->assertTrue($media->isFailed());
        $this->assertSame('timeout simulado', $media->error_message);
    }
}
