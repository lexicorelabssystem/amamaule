<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Territory;
use App\Models\User;
use App\Notifications\SendImportedCredentials;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportArtistsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public Import $import
    ) {}

    public function handle(): void
    {
        $this->import->update([
            'status' => Import::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $processed = 0;
        $success = 0;
        $failed = 0;

        ImportRow::query()
            ->where('import_id', $this->import->id)
            ->where('status', ImportRow::STATUS_PENDING)
            ->chunkById(100, function ($rows) use (&$processed, &$success, &$failed) {
                foreach ($rows as $row) {
                    $result = $this->processRow($row);
                    $processed++;

                    if ($result['success']) {
                        $success++;
                    } else {
                        $failed++;
                    }

                    if ($processed % 50 === 0) {
                        $this->updateProgress($processed, $success, $failed);
                    }
                }
            });

        $this->import->update([
            'status' => $failed > 0 && $success === 0
                ? Import::STATUS_FAILED
                : Import::STATUS_COMPLETED,
            'processed_rows' => $processed,
            'success_rows' => $success,
            'failed_rows' => $failed,
            'completed_at' => now(),
        ]);
    }

    protected function processRow(ImportRow $row): array
    {
        $data = $row->raw_data ?? [];

        $validator = Validator::make($data, [
            'legal_name' => ['required', 'string', 'max:255'],
            'email_contact' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            $row->markError($validator->errors()->toArray());

            return ['success' => false];
        }

        $email = strtolower(trim($data['email_contact']));

        if (User::where('email', $email)->exists()) {
            $row->markError(['email_contact' => ['El correo ya está registrado en la plataforma.']]);

            return ['success' => false];
        }

        $password = Str::password(16, true, true, true, false);

        try {
            [$user, $artist] = DB::transaction(function () use ($data, $email, $password) {
                $user = User::create([
                    'name' => $data['public_name'] ?? $data['legal_name'],
                    'email' => $email,
                    'password' => $password,
                    'status' => User::STATUS_ACTIVE,
                    'must_change_password' => true,
                ]);
                $user->assignRole('artista');

                $artist = Artist::create([
                    'user_id' => $user->id,
                    'legal_name' => $data['legal_name'],
                    'public_name' => $data['public_name'] ?? $data['legal_name'],
                    'artistic_name' => $data['artistic_name'] ?? null,
                    'email_contact' => $email,
                    'document_number' => $data['document_number'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'website' => $data['website'] ?? null,
                    'region' => $data['region'] ?? null,
                    'province' => $data['province'] ?? null,
                    'commune' => $data['commune'] ?? null,
                    'address' => $data['address'] ?? null,
                    'territory_id' => $this->resolveTerritoryId($data['commune'] ?? null),
                    'main_discipline_id' => $this->resolveDisciplineId($data['main_discipline'] ?? null),
                    'bio_short' => $data['bio_short'] ?? null,
                    'bio_long' => $data['bio_long'] ?? null,
                    'status' => Artist::STATUS_DRAFT,
                    'created_by' => $this->import->user_id,
                    'updated_by' => $this->import->user_id,
                ]);

                return [$user, $artist];
            });

            $user->notify(new SendImportedCredentials($password));

            $row->markSuccess($artist, $user);
        } catch (\Throwable $e) {
            $row->markError(['general' => ['No se pudo crear el artista: '.$e->getMessage()]]);

            return ['success' => false];
        }

        return ['success' => true];
    }

    protected function resolveTerritoryId(?string $commune): ?int
    {
        if (empty($commune)) {
            return null;
        }

        $territory = Territory::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($commune))])
            ->first();

        return $territory?->id;
    }

    protected function resolveDisciplineId(?string $discipline): ?int
    {
        if (empty($discipline)) {
            return null;
        }

        $disciplineModel = Discipline::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($discipline))])
            ->first();

        return $disciplineModel?->id;
    }

    protected function updateProgress(int $processed, int $success, int $failed): void
    {
        $this->import->update([
            'processed_rows' => $processed,
            'success_rows' => $success,
            'failed_rows' => $failed,
        ]);
    }
}
