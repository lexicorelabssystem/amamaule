<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

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

        Storage::put($path, (string) $processed->encodeByExtension(
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
