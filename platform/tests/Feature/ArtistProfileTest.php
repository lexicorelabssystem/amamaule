<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtistProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function artistUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('artista');
        Artist::factory()->create(['user_id' => $user->id, 'status' => Artist::STATUS_DRAFT]);

        return $user;
    }

    protected function reviewerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('revisor');

        return $user;
    }

    public function test_artist_can_view_profile_edit_form(): void
    {
        $user = $this->artistUser();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Mi perfil artístico');
    }

    public function test_artist_can_update_own_profile(): void
    {
        $user = $this->artistUser();
        $artist = $user->artist;

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'legal_name' => 'Nuevo Legal',
            'public_name' => 'Nuevo Público',
            'email_contact' => 'nuevo@example.com',
            'experience' => 'Más de 10 años de experiencia.',
            'portfolio_url' => 'https://portfolio.example.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $artist->refresh();
        $this->assertEquals('Nuevo Legal', $artist->legal_name);
        $this->assertEquals('nuevo@example.com', $artist->email_contact);

        $this->assertNotNull($artist->profile);
        $this->assertEquals('Más de 10 años de experiencia.', $artist->profile->experience);
        $this->assertEquals('https://portfolio.example.com', $artist->profile->portfolio_url);
    }

    public function test_artist_can_submit_profile_for_review(): void
    {
        $user = $this->artistUser();

        $response = $this->actingAs($user)->post(route('profile.submit'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $this->assertEquals(Artist::STATUS_SUBMITTED, $user->artist->fresh()->status);
        $this->assertNotNull($user->artist->fresh()->submitted_at);
    }

    public function test_reviewer_can_view_profile_review_index(): void
    {
        $reviewer = $this->reviewerUser();
        $artist = $this->artistUser()->artist;
        $artist->update(['status' => Artist::STATUS_SUBMITTED, 'submitted_at' => now()]);

        $response = $this->actingAs($reviewer)->get(route('profile-reviews.index'));

        $response->assertOk();
        $response->assertSee($artist->public_name);
    }

    public function test_reviewer_can_approve_profile(): void
    {
        $reviewer = $this->reviewerUser();
        $artist = $this->artistUser()->artist;
        $artist->update(['status' => Artist::STATUS_SUBMITTED, 'submitted_at' => now()]);

        $response = $this->actingAs($reviewer)
            ->patch(route('profile-reviews.approve', $artist));

        $response->assertRedirect(route('profile-reviews.index'));
        $this->assertEquals(Artist::STATUS_APPROVED, $artist->fresh()->status);
        $this->assertNotNull($artist->fresh()->approved_at);
        $this->assertEquals($reviewer->id, $artist->fresh()->approved_by);
    }

    public function test_reviewer_can_request_changes(): void
    {
        $reviewer = $this->reviewerUser();
        $artist = $this->artistUser()->artist;
        $artist->update(['status' => Artist::STATUS_SUBMITTED, 'submitted_at' => now()]);

        $response = $this->actingAs($reviewer)
            ->patch(route('profile-reviews.request-changes', $artist));

        $response->assertRedirect(route('profile-reviews.index'));
        $this->assertEquals(Artist::STATUS_NEEDS_CHANGES, $artist->fresh()->status);
    }

    public function test_reviewer_can_add_internal_comment(): void
    {
        $reviewer = $this->reviewerUser();
        $artist = $this->artistUser()->artist;

        $response = $this->actingAs($reviewer)->post(route('comments.store'), [
            'commentable_type' => Artist::class,
            'commentable_id' => $artist->id,
            'body' => 'Revisar biografía.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('comments', [
            'user_id' => $reviewer->id,
            'commentable_id' => $artist->id,
            'commentable_type' => Artist::class,
            'body' => 'Revisar biografía.',
        ]);
    }
}
