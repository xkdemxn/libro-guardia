<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('contactos.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_after_logout_private_pages_require_login_again(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/logout');

        $this->get(route('contactos.index'))->assertRedirect(route('login'));
    }

    public function test_users_can_authenticate_with_their_callsign_in_any_case(): void
    {
        User::factory()->create(['indicativo' => 'HC1QRC']);

        $this->post('/login', ['login' => 'hc1 qrc', 'password' => 'password']);

        $this->assertAuthenticated();
    }

    public function test_wrong_credentials_show_a_generic_message(): void
    {
        User::factory()->create(['indicativo' => 'HC1QRC']);

        $this->post('/login', ['login' => 'HC1QRC', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['login' => 'Indicativo o contraseña incorrectos.']);
    }

    public function test_access_is_locked_after_too_many_failed_attempts(): void
    {
        User::factory()->create(['indicativo' => 'HC1QRC']);

        for ($intento = 1; $intento <= LoginRequest::MAXIMO_INTENTOS; $intento++) {
            $this->post('/login', ['login' => 'HC1QRC', 'password' => 'wrong-password']);
        }

        $this->post('/login', ['login' => 'HC1QRC', 'password' => 'password'])
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }
}
