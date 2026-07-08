<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_view_import_upload_form(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user)->get(route('imports.create'));

        $response->assertOk();
        $response->assertSee('Importar artistas');
    }

    public function test_admin_can_upload_valid_csv_and_preview_rows(): void
    {
        $user = $this->adminUser();

        $csv = "legal_name,email_contact,public_name\n".
            "Juan Pérez,juan@example.com,Juanito\n".
            'Ana López,ana@example.com,Anita';

        $file = UploadedFile::fake()->createWithContent('artists.csv', $csv);

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'file' => $file,
        ]);

        $import = Import::first();

        $response->assertRedirect(route('imports.show', $import));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('imports', [
            'id' => $import->id,
            'original_filename' => 'artists.csv',
            'status' => 'pending',
            'total_rows' => 2,
        ]);

        $this->assertDatabaseHas('import_rows', [
            'import_id' => $import->id,
            'row_number' => 2,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('import_rows', [
            'import_id' => $import->id,
            'row_number' => 1,
        ]);
    }

    public function test_upload_fails_when_required_headers_are_missing(): void
    {
        $user = $this->adminUser();

        $csv = "nombre,email\nJuan,juan@example.com";
        $file = UploadedFile::fake()->createWithContent('artists.csv', $csv);

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('imports.create'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('imports', [
            'status' => 'failed',
        ]);

        $this->assertDatabaseCount('import_rows', 0);
    }

    public function test_import_preview_page_is_accessible(): void
    {
        $user = $this->adminUser();

        $csv = "legal_name,email_contact\nJuan,juan@example.com";
        $file = UploadedFile::fake()->createWithContent('artists.csv', $csv);

        $this->actingAs($user)->post(route('imports.store'), ['file' => $file]);
        $import = Import::first();

        $response = $this->actingAs($user)->get(route('imports.show', $import));

        $response->assertOk();
        $response->assertSee('juan@example.com');
    }
}
