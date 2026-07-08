<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\ProposalStatusChanged;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EpicSixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_proposal_review_is_audited_and_notifies_artist(): void
    {
        Notification::fake();
        $owner = User::factory()->create();
        $artist = Artist::factory()->create(['user_id' => $owner->id]);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('revisor');
        $proposal = Proposal::factory()->create(['artist_id' => $artist->id, 'status' => Proposal::STATUS_SUBMITTED]);
        $this->actingAs($reviewer)->patch(route('proposal-reviews.approve', $proposal), ['score' => 80])->assertRedirect();
        $this->assertDatabaseHas('audit_logs', ['event' => 'proposal.status_changed', 'auditable_id' => $proposal->id]);
        Notification::assertSentTo($owner, ProposalStatusChanged::class);
    }

    public function test_proposal_filters_preserve_only_matching_results(): void
    {
        $reviewer = User::factory()->create();
        $reviewer->assignRole('revisor');
        Proposal::factory()->create(['title' => 'Festival del Maule', 'status' => Proposal::STATUS_SUBMITTED]);
        Proposal::factory()->create(['title' => 'Otra iniciativa', 'status' => Proposal::STATUS_APPROVED]);
        $this->actingAs($reviewer)->get(route('proposals.index', ['search' => 'Maule', 'status' => Proposal::STATUS_SUBMITTED]))
            ->assertOk()->assertSee('Festival del Maule')->assertDontSee('Otra iniciativa');
    }

    public function test_admin_dashboard_shows_metrics_and_regular_user_does_not_see_admin_metrics(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('proposals pending');
        $artist = User::factory()->create();
        $artist->assignRole('artista');
        $this->actingAs($artist)->get(route('dashboard'))->assertOk()->assertDontSee('proposals pending');
    }
}
