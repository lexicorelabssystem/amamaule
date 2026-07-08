<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Proposal;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProposalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function artistUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('artista');
        Artist::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_artist_can_create_and_submit_own_proposal(): void
    {
        $user = $this->artistUser();
        $this->actingAs($user)->post(route('proposals.store'), [
            'title' => 'Propuesta cultural', 'description' => 'Descripcion completa',
        ])->assertRedirect();
        $proposal = Proposal::firstOrFail();
        $this->actingAs($user)->patch(route('proposals.submit', $proposal))->assertRedirect();
        $this->assertSame(Proposal::STATUS_SUBMITTED, $proposal->fresh()->status);
        $this->assertDatabaseHas('reviews', ['reviewable_id' => $proposal->id, 'new_status' => Proposal::STATUS_SUBMITTED]);
    }

    public function test_reviewer_can_approve_and_score_proposal(): void
    {
        $reviewer = User::factory()->create();
        $reviewer->assignRole('revisor');
        $proposal = Proposal::factory()->create(['status' => Proposal::STATUS_SUBMITTED]);
        $this->actingAs($reviewer)->patch(route('proposal-reviews.approve', $proposal), [
            'comment' => 'Cumple los criterios', 'score' => 90,
        ])->assertRedirect();
        $proposal->refresh();
        $this->assertSame(Proposal::STATUS_APPROVED, $proposal->status);
        $this->assertSame(90, $proposal->score);
        $this->assertSame($reviewer->id, $proposal->approved_by);
    }

    public function test_artist_cannot_edit_submitted_proposal(): void
    {
        $user = $this->artistUser();
        $proposal = Proposal::factory()->create(['artist_id' => $user->artist->id, 'status' => Proposal::STATUS_SUBMITTED]);
        $this->actingAs($user)->get(route('proposals.edit', $proposal))->assertForbidden();
    }

    public function test_artist_can_view_submitted_proposal_and_edit_after_changes_requested(): void
    {
        $user = $this->artistUser();
        $proposal = Proposal::factory()->create(['artist_id' => $user->artist->id, 'status' => Proposal::STATUS_SUBMITTED]);
        $this->actingAs($user)->get(route('proposals.show', $proposal))->assertOk();
        $proposal->update(['status' => Proposal::STATUS_NEEDS_CHANGES]);
        $this->actingAs($user)->get(route('proposals.edit', $proposal))->assertOk();
    }

    public function test_reviewer_can_request_changes_and_artist_can_resubmit(): void
    {
        $artist = $this->artistUser();
        $reviewer = User::factory()->create();
        $reviewer->assignRole('revisor');
        $proposal = Proposal::factory()->create(['artist_id' => $artist->artist->id, 'status' => Proposal::STATUS_IN_REVIEW]);
        $this->actingAs($reviewer)->patch(route('proposal-reviews.request-changes', $proposal), ['comment' => 'Falta detallar presupuesto'])->assertRedirect();
        $this->assertSame(Proposal::STATUS_NEEDS_CHANGES, $proposal->fresh()->status);
        $this->actingAs($artist)->patch(route('proposals.submit', $proposal))->assertRedirect();
        $this->assertSame(Proposal::STATUS_SUBMITTED, $proposal->fresh()->status);
        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_internal_proposal_comments_require_permission(): void
    {
        $artist = $this->artistUser();
        $proposal = Proposal::factory()->create(['artist_id' => $artist->artist->id]);
        $this->actingAs($artist)->post(route('comments.store'), [
            'commentable_type' => Proposal::class, 'commentable_id' => $proposal->id, 'body' => 'Interno',
        ])->assertForbidden();
        $reviewer = User::factory()->create();
        $reviewer->assignRole('revisor');
        $this->actingAs($reviewer)->post(route('comments.store'), [
            'commentable_type' => Proposal::class, 'commentable_id' => $proposal->id, 'body' => 'Revision interna',
        ])->assertRedirect();
        $this->assertDatabaseHas('comments', ['commentable_type' => Proposal::class, 'commentable_id' => $proposal->id, 'is_internal' => true]);
    }
}
