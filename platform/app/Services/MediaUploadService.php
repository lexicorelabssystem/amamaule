<?php

namespace App\Services;

use App\Jobs\ProcessMediaUploadJob;
use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use RuntimeException;

class MediaUploadService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    public function upload(UploadedFile $file, Model $model, string $collection = 'default', bool $isCover = false): Media
    {
        $directory = $this->directory($collection);
        $baseName = Str::uuid()->toString();
        $extension = $this->normalizeExtension($file);

        $fileName = "{$baseName}.{$extension}";
        $thumbnailName = "{$baseName}_thumb.{$extension}";

        $filePath = "{$directory}/{$fileName}";
        $thumbnailPath = "{$directory}/{$thumbnailName}";

        $image = $this->manager->read($file->getRealPath());

        $this->storeImage($image, $filePath, 1400, 1050, 85);
        $this->storeImage($image, $thumbnailPath, 400, 300, 80);

        $order = $model->media()->max('order') + 1;

        $media = $model->media()->create([
            'collection_name' => $collection,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'order' => $order,
            'is_cover' => $isCover,
        ]);

        if ($isCover) {
            $this->clearOtherCovers($media);
        }

        return $media;
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }

    public function queue(UploadedFile $file, Model $model, string $collection = 'default', bool $isCover = false): Media
    {
        $extension = $this->normalizeExtension($file);
        $pendingPath = $file->storeAs(
            "media-pending/{$collection}",
            Str::uuid()->toString().'.'.$extension,
            'public'
        );

        $order = $model->media()->max('order') + 1;

        $media = $model->media()->create([
            'collection_name' => $collection,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'order' => $order,
            'is_cover' => $isCover,
            'status' => Media::STATUS_QUEUED,
            'pending_path' => $pendingPath,
        ]);

        ProcessMediaUploadJob::dispatch($media);

        return $media;
    }

    public function process(Media $media): void
    {
        if (! $media->pending_path || ! Storage::disk('public')->exists($media->pending_path)) {
            throw new RuntimeException("No se encontró el archivo pendiente del media #{$media->id}.");
        }

        $directory = $this->directory($media->collection_name);
        $baseName = Str::uuid()->toString();
        $extension = $this->extensionFromPath($media->pending_path);

        $filePath = "{$directory}/{$baseName}.{$extension}";
        $thumbnailPath = "{$directory}/{$baseName}_thumb.{$extension}";

        $image = $this->manager->read(Storage::disk('public')->path($media->pending_path));

        $this->storeImage($image, $filePath, 1400, 1050, 85);
        $this->storeImage($image, $thumbnailPath, 400, 300, 80);

        Storage::disk('public')->delete($media->pending_path);

        $media->update([
            'file_path' => $filePath,
            'thumbnail_path' => $thumbnailPath,
            'status' => Media::STATUS_COMPLETED,
            'pending_path' => null,
            'error_message' => null,
        ]);

        if ($media->is_cover) {
            $this->clearOtherCovers($media);
        }
    }

    protected function directory(string $collection): string
    {
        return "media/{$collection}/".now()->format('Y/m');
    }

    protected function normalizeExtension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
    }

    protected function storeImage(ImageInterface $image, string $path, int $maxWidth, int $maxHeight, int $quality): void
    {
        $processed = clone $image;
        $processed->scaleDown(width: $maxWidth, height: $maxHeight);

        Storage::disk('public')->put($path, (string) $processed->encodeByExtension(
            extension: $this->extensionFromPath($path),
            quality: $quality,
        ));
    }

    protected function extensionFromPath(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
    }

    protected function clearOtherCovers(Media $media): void
    {
        Media::where('mediable_type', $media->mediable_type)
            ->where('mediable_id', $media->mediable_id)
            ->where('collection_name', $media->collection_name)
            ->where('id', '!=', $media->id)
            ->update(['is_cover' => false]);
    }
}
