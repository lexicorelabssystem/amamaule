<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Artist;
use App\Models\User;
use App\Models\WordPressPublication;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WordPressPublicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        config()->set('wordpress.url', 'https://wordpress.test');
        config()->set('wordpress.username', 'publisher');
        config()->set('wordpress.application_password', 'abcd efgh ijkl');
        config()->set('wordpress.allow_insecure', false);
    }

    private function publisher(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('comunicaciones');

        return $user;
    }

    public function test_publisher_can_publish_approved_artist_to_wordpress(): void
    {
        Http::fake(['*/wp-json/wp/v2/posts' => Http::response(['id' => 77, 'link' => 'https://wp.test/artista'], 201)]);
        $artist = Artist::factory()->create(['public_name' => 'Artista AMA', 'status' => Artist::STATUS_APPROVED]);

        $this->actingAs($this->publisher())
            ->post(route('artists.wordpress.publish', $artist))
            ->assertRedirect();

        $this->assertDatabaseHas('wordpress_publications', [
            'publishable_type' => Artist::class,
            'publishable_id' => $artist->id,
            'wordpress_post_id' => 77,
            'status' => WordPressPublication::STATUS_PUBLISHED,
        ]);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://wordpress.test/wp-json/wp/v2/posts'
            && $request['title'] === 'Artista AMA'
            && $request['status'] === 'publish');
    }

    public function test_publisher_can_publish_activity_and_then_unpublish_it(): void
    {
        Http::fake([
            '*/wp-json/wp/v2/posts' => Http::response(['id' => 88, 'link' => 'https://wp.test/actividad'], 201),
            '*/wp-json/wp/v2/posts/88' => Http::response(['id' => 88, 'status' => 'draft', 'link' => 'https://wp.test/actividad']),
        ]);
        $activity = Activity::factory()->create(['title' => 'Actividad AMA', 'status' => Activity::STATUS_PUBLISHED]);

        $this->actingAs($this->publisher())
            ->post(route('activities.wordpress.publish', $activity))
            ->assertRedirect();

        $this->actingAs($this->publisher())
            ->patch(route('activities.wordpress.unpublish', $activity))
            ->assertRedirect();

        $this->assertSame(WordPressPublication::STATUS_DRAFT, $activity->fresh()->wordpressPublication->status);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://wordpress.test/wp-json/wp/v2/posts/88'
            && $request['status'] === 'draft');
    }

    public function test_existing_publication_is_synchronized_with_update_request(): void
    {
        Http::fake(['*/wp-json/wp/v2/posts/91' => Http::response(['id' => 91, 'link' => 'https://wp.test/sync'])]);
        $artist = Artist::factory()->create(['public_name' => 'Nombre actualizado', 'status' => Artist::STATUS_APPROVED]);
        $artist->wordpressPublication()->create([
            'wordpress_post_id' => 91,
            'status' => WordPressPublication::STATUS_PUBLISHED,
        ]);

        $this->actingAs($this->publisher())
            ->post(route('artists.wordpress.publish', $artist))
            ->assertRedirect();

        Http::assertSent(fn (Request $request) => $request->url() === 'https://wordpress.test/wp-json/wp/v2/posts/91'
            && $request['title'] === 'Nombre actualizado');
    }

    public function test_failed_publication_is_recorded_for_retry(): void
    {
        Http::fake(['*/wp-json/wp/v2/posts' => Http::response(['message' => 'No autorizado'], 401)]);
        $artist = Artist::factory()->create(['status' => Artist::STATUS_APPROVED]);

        $this->actingAs($this->publisher())
            ->post(route('artists.wordpress.publish', $artist))
            ->assertRedirect()
            ->assertSessionHas('error');

        $publication = $artist->fresh()->wordpressPublication;
        $this->assertSame(WordPressPublication::STATUS_FAILED, $publication->status);
        $this->assertSame(1, $publication->attempts);
        $this->assertNotNull($publication->last_error);
    }

    public function test_unapproved_artist_cannot_be_published(): void
    {
        $artist = Artist::factory()->create(['status' => Artist::STATUS_DRAFT]);

        $this->actingAs($this->publisher())
            ->post(route('artists.wordpress.publish', $artist))
            ->assertStatus(422);
    }
}
