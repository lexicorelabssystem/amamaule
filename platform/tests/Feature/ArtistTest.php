<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Discipline;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\DisciplineSeeder::class);
        $this->seed(\Database\Seeders\TerritorySeeder::class);
    }

    private function adminUser(): User
    {
        $user = User::factory()->create([
            'email' => 'admin-test@amamaule.cl',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'must_change_password' => false,
        ]);
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_admin_can_view_artists_list(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('artists.index'));

        $response->assertStatus(200);
        $response->assertSee('Artistas');
    }

    public function test_admin_can_create_an_artist(): void
    {
        $admin = $this->adminUser();
        $discipline = Discipline::first();
        $territory = Territory::first();

        $response = $this->actingAs($admin)->post(route('artists.store'), [
            'legal_name' => 'Juan Pérez',
            'public_name' => 'Juan Art',
            'email_contact' => 'juan@example.com',
            'phone' => '+56912345678',
            'territory_id' => $territory->id,
            'main_discipline_id' => $discipline->id,
            'disciplines' => [$discipline->id],
            'bio_short' => 'Breve biografía',
            'status' => Artist::STATUS_DRAFT,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('artists', [
            'legal_name' => 'Juan Pérez',
            'public_name' => 'Juan Art',
            'email_contact' => 'juan@example.com',
        ]);
    }

    public function test_admin_can_update_an_artist(): void
    {
        $admin = $this->adminUser();
        $artist = Artist::factory()->create([
            'legal_name' => 'Original',
            'public_name' => 'Original Art',
            'status' => Artist::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($admin)->put(route('artists.update', $artist), [
            'legal_name' => 'Actualizado',
            'public_name' => 'Actualizado Art',
            'email_contact' => $artist->email_contact,
            'status' => Artist::STATUS_DRAFT,
        ]);

        $response->assertRedirect(route('artists.show', $artist));
        $this->assertDatabaseHas('artists', [
            'id' => $artist->id,
            'legal_name' => 'Actualizado',
        ]);
    }

    public function test_admin_can_delete_an_artist(): void
    {
        $admin = $this->adminUser();
        $artist = Artist::factory()->create();

        $response = $this->actingAs($admin)->delete(route('artists.destroy', $artist));

        $response->assertRedirect(route('artists.index'));
        $this->assertSoftDeleted('artists', ['id' => $artist->id]);
    }

    public function test_artist_cannot_be_created_with_invalid_email(): void
    {
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->post(route('artists.store'), [
            'legal_name' => 'Test',
            'email_contact' => 'no-es-un-email',
            'status' => Artist::STATUS_DRAFT,
        ]);

        $response->assertSessionHasErrors(['email_contact']);
    }
}
