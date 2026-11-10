<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'indicativo' => 'hc1 qrc',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'email' => 'test@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('contactos.index', absolute: false));
        $this->assertDatabaseHas('users', ['indicativo' => 'HC1QRC']);
    }

    public function test_a_registered_callsign_cannot_be_used_again(): void
    {
        User::factory()->create(['indicativo' => 'HC1QRC']);

        $this->post('/register', [
            'indicativo' => 'hc1qrc',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'email' => 'otra@example.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
        ])->assertSessionHasErrors(['indicativo' => 'Este indicativo ya está registrado.']);

        $this->assertGuest();
    }

    public function test_weak_passwords_are_rejected(): void
    {
        $this->post('/register', [
            'indicativo' => 'HC1QRC',
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }
}
