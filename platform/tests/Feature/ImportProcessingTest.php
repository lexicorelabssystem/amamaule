<?php

namespace Tests\Feature;

use App\Jobs\ImportArtistsJob;
use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\Territory;
use App\Models\User;
use App\Notifications\SendImportedCredentials;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ImportProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_job_creates_users_and_artists_from_valid_rows(): void
    {
        $admin = $this->adminUser();

        $discipline = Discipline::create(['name' => 'Música', 'slug' => 'musica']);
        $territory = Territory::create(['name' => 'Talca', 'region' => 'Maule', 'slug' => 'talca']);

        $import = Import::factory()->create([
            'user_id' => $admin->id,
            'status' => Import::STATUS_PENDING,
            'total_rows' => 2,
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => [
                'legal_name' => 'Juan Pérez',
                'email_contact' => 'juan@example.com',
                'public_name' => 'Juanito',
                'main_discipline' => 'Música',
                'commune' => 'Talca',
            ],
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 3,
            'raw_data' => [
                'legal_name' => 'Ana López',
                'email_contact' => 'ana@example.com',
            ],
        ]);

        ImportArtistsJob::dispatch($import);

        $import->refresh();

        $this->assertEquals(Import::STATUS_COMPLETED, $import->status);
        $this->assertEquals(2, $import->success_rows);
        $this->assertEquals(0, $import->failed_rows);
        $this->assertEquals(2, $import->processed_rows);

        $this->assertDatabaseHas('users', ['email' => 'juan@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);

        $artist = Artist::where('email_contact', 'juan@example.com')->first();
        $this->assertNotNull($artist);
        $this->assertEquals($discipline->id, $artist->main_discipline_id);
        $this->assertEquals($territory->id, $artist->territory_id);
        $this->assertTrue($artist->user->hasRole('artista'));
    }

    public function test_job_marks_duplicate_emails_as_errors(): void
    {
        $admin = $this->adminUser();

        User::factory()->create(['email' => 'duplicado@example.com']);

        $import = Import::factory()->create([
            'user_id' => $admin->id,
            'status' => Import::STATUS_PENDING,
            'total_rows' => 1,
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => [
                'legal_name' => 'Usuario Duplicado',
                'email_contact' => 'duplicado@example.com',
            ],
        ]);

        ImportArtistsJob::dispatch($import);

        $import->refresh();

        $this->assertEquals(Import::STATUS_FAILED, $import->status);
        $this->assertEquals(0, $import->success_rows);
        $this->assertEquals(1, $import->failed_rows);

        $row = $import->rows->first();
        $this->assertEquals('error', $row->status);
        $this->assertArrayHasKey('email_contact', $row->errors);
    }

    public function test_job_marks_missing_required_fields_as_errors(): void
    {
        $admin = $this->adminUser();

        $import = Import::factory()->create([
            'user_id' => $admin->id,
            'status' => Import::STATUS_PENDING,
            'total_rows' => 1,
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => [
                'legal_name' => 'Sin Email',
            ],
        ]);

        ImportArtistsJob::dispatch($import);

        $import->refresh();

        $this->assertEquals(Import::STATUS_FAILED, $import->status);
        $this->assertEquals(0, $import->success_rows);
        $this->assertEquals(1, $import->failed_rows);

        $row = $import->rows->first();
        $this->assertEquals('error', $row->status);
        $this->assertArrayHasKey('email_contact', $row->errors);
    }

    public function test_controller_process_dispatches_job(): void
    {
        $admin = $this->adminUser();

        $import = Import::factory()->create([
            'user_id' => $admin->id,
            'status' => Import::STATUS_PENDING,
            'total_rows' => 1,
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => [
                'legal_name' => 'Carlos',
                'email_contact' => 'carlos@example.com',
            ],
        ]);

        $response = $this->actingAs($admin)
            ->post(route('imports.process', $import));

        $response->assertRedirect(route('imports.show', $import));
        $response->assertSessionHas('success');

        $import->refresh();
        $this->assertEquals(Import::STATUS_COMPLETED, $import->status);
        $this->assertDatabaseHas('users', ['email' => 'carlos@example.com']);
    }

    public function test_job_sends_credentials_notification_to_new_user(): void
    {
        $admin = $this->adminUser();
        Notification::fake();

        $import = Import::factory()->create([
            'user_id' => $admin->id,
            'status' => Import::STATUS_PENDING,
            'total_rows' => 1,
        ]);

        ImportRow::factory()->create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => [
                'legal_name' => 'Pedro',
                'email_contact' => 'pedro@example.com',
            ],
        ]);

        ImportArtistsJob::dispatch($import);

        $user = User::where('email', 'pedro@example.com')->first();

        $this->assertNotNull($user);
        Notification::assertSentTo(
            $user,
            SendImportedCredentials::class,
            function (SendImportedCredentials $notification) {
                return ! empty($notification->plainPassword);
            }
        );
    }
}
