<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanMedia extends Command
{
    protected $signature = 'media:cleanup {--dry-run : Muestra los archivos a eliminar sin borrarlos}';

    protected $description = 'Elimina archivos de media que no tienen registro en la base de datos';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $disk = Storage::disk('local');

        if (! $disk->exists('media')) {
            $this->info('No existe el directorio de media.');

            return self::SUCCESS;
        }

        $trackedPaths = Media::pluck('file_path')
            ->merge(Media::pluck('thumbnail_path'))
            ->filter()
            ->unique()
            ->values();

        $deleted = 0;
        $checked = 0;

        foreach ($disk->allFiles('media') as $relativePath) {
            $checked++;

            if ($trackedPaths->contains($relativePath)) {
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY-RUN] Se eliminaría: {$relativePath}");
                $deleted++;

                continue;
            }

            $disk->delete($relativePath);
            $this->line("Eliminado: {$relativePath}");
            $deleted++;
        }

        $this->info("Revisados: {$checked}. Eliminados: {$deleted}.");

        return self::SUCCESS;
    }
}
