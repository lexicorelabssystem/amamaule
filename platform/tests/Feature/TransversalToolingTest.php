<?php

namespace Tests\Feature;

use Tests\TestCase;

class TransversalToolingTest extends TestCase
{
    public function test_composer_exposes_quality_and_integration_scripts(): void
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        $this->assertArrayHasKey('lint', $composer['scripts']);
        $this->assertArrayHasKey('format', $composer['scripts']);
        $this->assertArrayHasKey('test:integration', $composer['scripts']);
    }

    public function test_structured_logging_channels_are_configured(): void
    {
        $this->assertSame('daily', config('logging.channels.ama.driver'));
        $this->assertSame('daily', config('logging.channels.audit.driver'));
        $this->assertSame('daily', config('logging.channels.integrations.driver'));
        $this->assertStringEndsWith('ama.log', config('logging.channels.ama.path'));
    }
}
