<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class WordPressPublisher
{
    private string $baseUrl;

    private string $username;

    private string $applicationPassword;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('wordpress.url'), '/');
        $this->username = (string) config('wordpress.username');
        $this->applicationPassword = (string) config('wordpress.application_password');
        $this->validateConfiguration();
    }

    public function testConnection(): array
    {
        return $this->request()->get($this->endpoint('users/me'), ['context' => 'edit'])->throw()->json();
    }

    public function createPost(array $payload, string $postType = 'posts'): array
    {
        return $this->request()->post($this->endpoint($postType), $payload)->throw()->json();
    }

    public function updatePost(int $postId, array $payload, string $postType = 'posts'): array
    {
        return $this->request()->post($this->endpoint($postType.'/'.$postId), $payload)->throw()->json();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()->asJson()
            ->withBasicAuth($this->username, $this->applicationPassword)
            ->connectTimeout((int) config('wordpress.connect_timeout', 5))
            ->timeout((int) config('wordpress.timeout', 15));
    }

    private function endpoint(string $path): string
    {
        return $this->baseUrl.'/wp-json/wp/v2/'.ltrim($path, '/');
    }

    private function validateConfiguration(): void
    {
        if ($this->baseUrl === '' || $this->username === '' || $this->applicationPassword === '') {
            throw new InvalidArgumentException('WordPress REST configuration is incomplete.');
        }
        $parts = parse_url($this->baseUrl);
        if (! is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('WORDPRESS_URL must be a valid URL without embedded credentials.');
        }
        if (($parts['scheme'] ?? null) !== 'https' && ! config('wordpress.allow_insecure')) {
            throw new InvalidArgumentException('WordPress REST requires HTTPS unless local insecure mode is explicitly enabled.');
        }
    }
}
