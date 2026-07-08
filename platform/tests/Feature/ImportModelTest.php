<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $import = Import::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($import->user->is($user));
    }

    public function test_import_has_many_rows(): void
    {
        $import = Import::factory()->create();
        $rows = ImportRow::factory()->count(3)->create(['import_id' => $import->id]);

        $this->assertCount(3, $import->rows);
        $this->assertContains($rows->first()->id, $import->rows->pluck('id'));
    }

    public function test_import_row_belongs_to_import(): void
    {
        $import = Import::factory()->create();
        $row = ImportRow::factory()->create(['import_id' => $import->id]);

        $this->assertTrue($row->import->is($import));
    }

    public function test_import_row_may_belong_to_artist_and_user(): void
    {
        $import = Import::factory()->create();
        $artist = Artist::factory()->create();
        $user = User::factory()->create();

        $row = ImportRow::factory()->create([
            'import_id' => $import->id,
            'artist_id' => $artist->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($row->artist->is($artist));
        $this->assertTrue($row->user->is($user));
    }

    public function test_user_has_many_imports(): void
    {
        $user = User::factory()->create();
        Import::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->imports);
    }

    public function test_import_row_status_helpers(): void
    {
        $row = ImportRow::factory()->create();

        $row->markError(['email' => 'El email ya existe']);
        $this->assertEquals('error', $row->fresh()->status);
        $this->assertContains('El email ya existe', $row->fresh()->errors);

        $artist = Artist::factory()->create();
        $row->markSuccess($artist);
        $this->assertEquals('success', $row->fresh()->status);
        $this->assertTrue($row->fresh()->artist->is($artist));
    }
}
