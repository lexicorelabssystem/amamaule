<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\WordPressPublication;
use App\Services\WordPressPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class WordPressPublisherTest extends TestCase
{
    use RefreshDatabase;

    private function configure(string $url = 'https://wordpress.test'): void
    {
        config()->set('wordpress.url', $url);
        config()->set('wordpress.username', 'publisher');
        config()->set('wordpress.application_password', 'abcd efgh ijkl');
        config()->set('wordpress.allow_insecure', false);
    }

    public function test_it_rejects_incomplete_or_insecure_configuration(): void
    {
        config()->set('wordpress', []);
        $this->expectException(InvalidArgumentException::class);
        new WordPressPublisher;
    }

    public function test_http_requires_explicit_local_override(): void
    {
        $this->configure('http://wordpress.test');
        $this->expectException(InvalidArgumentException::class);
        new WordPressPublisher;
    }

    public function test_connection_uses_wordpress_me_endpoint_and_basic_auth(): void
    {
        $this->configure();
        Http::fake(['https://wordpress.test/wp-json/wp/v2/users/me*' => Http::response(['id' => 7, 'name' => 'API Publisher'])]);
        $result = app(WordPressPublisher::class)->testConnection();
        $this->assertSame(7, $result['id']);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://wordpress.test/wp-json/wp/v2/users/me?context=edit'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('publisher:abcd efgh ijkl')));
    }

    public function test_client_can_create_and_update_posts_with_simulated_http(): void
    {
        $this->configure();
        Http::fake([
            '*/wp-json/wp/v2/posts' => Http::response(['id' => 55, 'status' => 'publish'], 201),
            '*/wp-json/wp/v2/posts/55' => Http::response(['id' => 55, 'status' => 'draft']),
        ]);
        $client = app(WordPressPublisher::class);
        $this->assertSame(55, $client->createPost(['title' => 'Ficha artista'])['id']);
        $this->assertSame('draft', $client->updatePost(55, ['status' => 'draft'])['status']);
    }

    public function test_publication_belongs_to_publishable_model(): void
    {
        $artist = Artist::factory()->create();
        $publication = $artist->wordpressPublication()->create(['status' => WordPressPublication::STATUS_PENDING]);
        $this->assertTrue($publication->publishable->is($artist));
        $this->assertTrue($artist->fresh()->wordpressPublication->is($publication));
    }
}
