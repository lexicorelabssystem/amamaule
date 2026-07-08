<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\CommunityChannel;
use App\Models\CommunityMessage;
use App\Models\Discipline;
use App\Models\ModerationReport;
use App\Models\Territory;
use App\Models\User;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TerritorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EpicNineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
        $this->seed(TerritorySeeder::class);
        Cache::flush();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole($role);

        return $user;
    }

    private function channel(): CommunityChannel
    {
        return CommunityChannel::create([
            'discipline_id' => Discipline::first()->id,
            'name' => 'Canal M?sica',
            'slug' => 'canal-musica',
            'description' => 'Conversaci?n por disciplina',
        ]);
    }

    public function test_artist_can_view_channels_and_send_message(): void
    {
        $artist = $this->userWithRole('artista');
        $channel = $this->channel();

        $this->actingAs($artist)
            ->get(route('community.channels.index'))
            ->assertOk()
            ->assertSee('Canal M?sica');

        $this->actingAs($artist)
            ->post(route('community.messages.store', $channel), ['body' => 'Hola comunidad AMA'])
            ->assertRedirect(route('community.channels.show', $channel));

        $this->assertDatabaseHas('community_messages', [
            'community_channel_id' => $channel->id,
            'user_id' => $artist->id,
            'body' => 'Hola comunidad AMA',
            'status' => CommunityMessage::STATUS_VISIBLE,
        ]);
    }

    public function test_moderator_can_resolve_report_and_hide_message(): void
    {
        $reporter = $this->userWithRole('artista');
        $moderator = $this->userWithRole('revisor');
        $message = CommunityMessage::create([
            'community_channel_id' => $this->channel()->id,
            'user_id' => $reporter->id,
            'body' => 'Mensaje a revisar',
        ]);

        $this->actingAs($reporter)
            ->post(route('moderation-reports.store', $message), [
                'reason' => 'Contenido sensible',
                'details' => 'Revisar tono del mensaje',
            ])
            ->assertRedirect();

        $report = ModerationReport::firstOrFail();

        $this->actingAs($moderator)
            ->patch(route('moderation-reports.resolve', $report), ['hide_content' => true])
            ->assertRedirect(route('moderation-reports.index'));

        $this->assertSame(ModerationReport::STATUS_RESOLVED, $report->fresh()->status);
        $this->assertSame(CommunityMessage::STATUS_HIDDEN, $message->fresh()->status);
    }

    public function test_public_catalog_api_returns_only_approved_artists_and_uses_cache(): void
    {
        $territory = Territory::first();
        $discipline = Discipline::first();
        Artist::factory()->create([
            'public_name' => 'Cat?logo P?blico',
            'territory_id' => $territory->id,
            'main_discipline_id' => $discipline->id,
            'status' => Artist::STATUS_APPROVED,
        ]);
        Artist::factory()->create(['public_name' => 'Borrador Privado', 'status' => Artist::STATUS_DRAFT]);

        $this->getJson('/api/catalog/artists')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cat?logo P?blico'])
            ->assertJsonMissing(['name' => 'Borrador Privado']);

        $this->assertTrue(Cache::has('public_catalog.artists'));
    }

    public function test_public_catalog_api_returns_only_published_activities(): void
    {
        $artist = Artist::factory()->create(['status' => Artist::STATUS_APPROVED]);
        Activity::factory()->create([
            'artist_id' => $artist->id,
            'title' => 'Actividad p?blica',
            'status' => Activity::STATUS_PUBLISHED,
        ]);
        Activity::factory()->create([
            'artist_id' => $artist->id,
            'title' => 'Actividad borrador',
            'status' => Activity::STATUS_DRAFT,
        ]);

        $this->getJson('/api/catalog/activities')
            ->assertOk()
            ->assertJsonFragment(['title' => 'Actividad p?blica'])
            ->assertJsonMissing(['title' => 'Actividad borrador']);

        $this->assertTrue(Cache::has('public_catalog.activities'));
    }
}
