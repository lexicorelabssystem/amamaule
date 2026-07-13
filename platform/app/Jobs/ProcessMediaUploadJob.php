<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaUploadService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;
use Throwable;

class ProcessMediaUploadJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 60;

    public int $tries = 1;

    public function __construct(public Media $media) {}

    public function handle(MediaUploadService $mediaUpload): void
    {
        $this->media->update(['status' => Media::STATUS_PROCESSING]);

        try {
            $mediaUpload->process($this->media);
        } catch (Throwable $e) {
            $this->markFailed($e);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception);
    }

    protected function markFailed(?Throwable $exception): void
    {
        $this->media->deletePendingFile();

        $this->media->update([
            'status' => Media::STATUS_FAILED,
            'pending_path' => null,
            'error_message' => $exception
                ? Str::limit($exception->getMessage(), 250)
                : 'No se pudo procesar la imagen.',
        ]);
    }
}
