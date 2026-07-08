<?php

namespace Tests\Integration\WordPress;

use App\Services\WordPressPublisher;
use Tests\TestCase;

class WordPressPublishingIntegrationTest extends TestCase
{
    public function test_real_wordpress_connection_can_be_checked_when_enabled(): void
    {
        if (! filter_var(env('RUN_WORDPRESS_INTEGRATION', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Set RUN_WORDPRESS_INTEGRATION=true and configure WORDPRESS_* env vars to hit a real WordPress site.');
        }

        $response = app(WordPressPublisher::class)->testConnection();

        $this->assertArrayHasKey('id', $response);
    }
}
