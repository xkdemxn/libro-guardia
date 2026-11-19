<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

/**
 * Autenticación de la SPA de React: sesión por cookie con Sanctum.
 */
class AutenticacionApiTest extends ApiTestCase
{
    use RefreshDatabase;

    private const SECRETO_APP = 'JBSWY3DPEHPK3PXP';

    public function test_without_a_session_the_current_user_is_unauthorized(): void
    {
        $this->getJson(route('api.usuario'))->assertUnauthorized();
    }

    public function test_an_operator_logs_in_with_the_callsign_and_gets_their_data(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('api.sesion.store'), [
            'login' => strtolower($user->indicativo),
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('segundo_factor', false)
            ->assertJsonPath('usuario.indicativo', $user->indicativo);

        $this->assertAuthenticatedAs($user);
        $this->getJson(route('api.usuario'))->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_a_wrong_password_is_rejected_without_starting_a_session(): void
    {
        $user = User::factory()->create();

        $this->postJson(route('api.sesion.store'), [
            'login' => $user->email,
            'password' => 'incorrecta',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['login' => 'Indicativo o contraseña incorrectos.']);

        $this->assertGuest();
    }

    public function test_an_operator_with_the_app_factor_must_pass_the_challenge(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt(self::SECRETO_APP),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->postJson(route('api.sesion.store'), [
            'login' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('segundo_factor', true)
            ->assertJsonPath('metodos.app', true)
            ->assertJsonMissingPath('usuario');
        $this->assertGuest();

        $this->getJson(route('api.segundo-factor.show'))->assertOk()->assertJsonPath('metodos.app', true);

        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'app', 'codigo' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo' => 'El código es inválido o ha vencido.']);
        $this->assertGuest();

        $codigo = (new Google2FA)->getCurrentOtp(self::SECRETO_APP);
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'app', 'codigo' => $codigo])
            ->assertOk()
            ->assertJsonPath('data.indicativo', $user->indicativo);
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_challenge_requires_a_pending_login(): void
    {
        $this->getJson(route('api.segundo-factor.show'))->assertNotFound();
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'app', 'codigo' => '123456'])->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_operator_can_register_and_is_logged_in(): void
    {
        Notification::fake();

        $this->postJson(route('api.registro.store'), [
            'indicativo' => 'hc1abc',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'email' => 'ana@example.com',
            'password' => 'clave1234',
            'password_confirmation' => 'clave1234',
        ])->assertCreated()->assertJsonPath('data.indicativo', 'HC1ABC');

        $user = User::where('indicativo', 'HC1ABC')->sole();
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_a_callsign_cannot_be_registered_twice(): void
    {
        User::factory()->create(['indicativo' => 'HC1ABC']);

        $this->postJson(route('api.registro.store'), [
            'indicativo' => 'HC1ABC',
            'nombres' => 'Ana',
            'apellidos' => 'Torres',
            'email' => 'ana@example.com',
            'password' => 'clave1234',
            'password_confirmation' => 'clave1234',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['indicativo' => 'Este indicativo ya está registrado.']);
    }

    public function test_logging_out_closes_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->deleteJson(route('api.sesion.destroy'))->assertNoContent();

        $this->assertGuest('web');
    }

    public function test_the_reset_link_points_to_the_spa_and_the_answer_does_not_reveal_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->postJson(route('api.contrasena.enlace'), ['email' => 'nadie@example.com'])->assertOk();
        $respuesta = $this->postJson(route('api.contrasena.enlace'), ['email' => $user->email])->assertOk();

        $respuesta->assertJsonPath('mensaje', 'Si el correo está registrado, te enviamos un enlace para restablecer tu contraseña. El enlace caduca en 60 minutos.');
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notificacion) use ($user): bool {
            $url = $notificacion->toMail($user)->actionUrl;

            return str_starts_with($url, config('app.frontend_url').'/restablecer-contrasena/'.$notificacion->token);
        });
    }

    public function test_the_password_is_reset_with_a_valid_token_only(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->postJson(route('api.contrasena.restablecer'), [
            'token' => 'token-falso',
            'email' => $user->email,
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->postJson(route('api.contrasena.restablecer'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva1234', $user->fresh()->password));
    }

    public function test_an_unverified_operator_can_ask_for_a_new_verification_link(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->postJson(route('api.correo.verificacion'))->assertAccepted();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
