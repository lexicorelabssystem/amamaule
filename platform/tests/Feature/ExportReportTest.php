<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TerritorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
        $this->seed(TerritorySeeder::class);
    }

    private function reviewer(): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'must_change_password' => false]);
        $user->assignRole('revisor');

        return $user;
    }

    public function test_reviewer_can_export_filtered_artists_as_csv(): void
    {
        $territory = Territory::first();
        $discipline = Discipline::first();

        Artist::factory()->create([
            'public_name' => 'Compa??a R?o Claro',
            'legal_name' => 'Agrupaci?n R?o Claro',
            'territory_id' => $territory->id,
            'main_discipline_id' => $discipline->id,
            'status' => Artist::STATUS_APPROVED,
        ]);
        Artist::factory()->create(['public_name' => 'Otra banda', 'status' => Artist::STATUS_DRAFT]);

        $response = $this->actingAs($this->reviewer())->get(route('exports.artists', [
            'format' => 'csv',
            'search' => 'R?o',
            'status' => Artist::STATUS_APPROVED,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Nombre publico', $content);
        $this->assertStringContainsString('Compa??a R?o Claro', $content);
        $this->assertStringNotContainsString('Otra banda', $content);
    }

    public function test_csv_export_escapes_formula_like_artist_values(): void
    {
        Artist::factory()->create(['public_name' => '=CMD()', 'legal_name' => '+SUM(1,1)']);

        $response = $this->actingAs($this->reviewer())->get(route('exports.artists', ['format' => 'csv']));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString("'=CMD()", $content);
        $this->assertStringContainsString("'+SUM(1,1)", $content);
    }

    public function test_reviewer_can_export_activities_as_xlsx(): void
    {
        $territory = Territory::first();
        $artist = Artist::factory()->create(['public_name' => 'Elenco Maule']);
        Activity::factory()->create([
            'artist_id' => $artist->id,
            'territory_id' => $territory->id,
            'title' => 'Gala de invierno',
            'status' => Activity::STATUS_PUBLISHED,
        ]);

        $response = $this->actingAs($this->reviewer())->get(route('exports.activities', ['format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_artist_without_export_permission_cannot_download_exports(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'must_change_password' => false]);
        $user->assignRole('artista');

        $this->actingAs($user)->get(route('exports.artists', ['format' => 'csv']))->assertForbidden();
    }

    public function test_reviewer_can_view_basic_reports(): void
    {
        $territory = Territory::first();
        $discipline = Discipline::first();
        Artist::factory()->create([
            'public_name' => 'Artista reportado',
            'territory_id' => $territory->id,
            'main_discipline_id' => $discipline->id,
        ]);

        $this->actingAs($this->reviewer())
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Reportes administrativos')
            ->assertSee($territory->name)
            ->assertSee($discipline->name);
    }
}
