<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@amamaule.cl',
            'password' => Hash::make('Password123!'),
            'must_change_password' => false,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('artista');

        Livewire::test('pages.auth.login')
            ->set('form.email', 'test@amamaule.cl')
            ->set('form.password', 'Password123!')
            ->call('login')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_users_with_must_change_password_are_redirected_to_password_change(): void
    {
        $user = User::factory()->create([
            'email' => 'change@amamaule.cl',
            'password' => Hash::make('TempPass2026!'),
            'must_change_password' => true,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $user->assignRole('artista');

        Livewire::test('pages.auth.login')
            ->set('form.email', 'change@amamaule.cl')
            ->set('form.password', 'TempPass2026!')
            ->call('login');

        $this->assertAuthenticatedAs($user);

        $response = $this->get('/dashboard');
        $response->assertRedirect(route('password.change', absolute: false));
    }

    public function test_suspended_users_cannot_authenticate(): void
    {
        User::factory()->create([
            'email' => 'suspended@amamaule.cl',
            'password' => Hash::make('Password123!'),
            'status' => User::STATUS_SUSPENDED,
            'email_verified_at' => now(),
        ]);

        Livewire::test('pages.auth.login')
            ->set('form.email', 'suspended@amamaule.cl')
            ->set('form.password', 'Password123!')
            ->call('login')
            ->assertHasErrors(['form.email']);

        $this->assertGuest();
    }
}
