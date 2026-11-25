<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_codes_require_a_verified_address_and_the_current_password(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertSessionHasErrorsIn('segundoFactor', ['accion']);

        $user->markEmailAsVerified();

        $this->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'incorrecta',
        ])->assertSessionHasErrorsIn('segundoFactor', ['password']);

        $this->assertFalse($user->fresh()->two_factor_email_enabled);
    }

    public function test_email_factor_cannot_be_enabled_with_the_log_mailer(): void
    {
        config(['mail.default' => 'log']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertSessionHasErrorsIn('segundoFactor', ['accion']);

        $this->assertFalse($user->fresh()->two_factor_email_enabled);
    }

    public function test_email_factor_cannot_be_enabled_without_the_brevo_api_key(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => '']);
        Http::preventStrayRequests();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertSessionHasErrorsIn('segundoFactor', ['accion' => 'El envío de correo todavía no está configurado.']);

        $this->assertFalse($user->fresh()->two_factor_email_enabled);
        Http::assertNothingSent();
    }

    public function test_email_factor_cannot_be_enabled_without_the_gmail_app_password(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'operador@example.com',
            'mail.mailers.smtp.password' => null,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertSessionHasErrorsIn('segundoFactor', ['accion' => 'El envío de correo todavía no está configurado.']);

        $this->assertFalse($user->fresh()->two_factor_email_enabled);
    }

    public function test_email_factor_can_be_enabled_with_gmail_credentials(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'operador@example.com',
            'mail.mailers.smtp.password' => 'clave-de-prueba',
            'mail.from.address' => 'operador@example.com',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();

        $this->assertTrue($user->fresh()->two_factor_email_enabled);
    }

    public function test_email_factor_can_complete_login_with_a_code_sent_through_brevo(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<acceso@brevo>'], 201)]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->two_factor_email_enabled);

        $this->post('/logout');
        $this->iniciarSesion($user);
        $this->post(route('two-factor.send'))->assertRedirect(route('two-factor.show'))->assertSessionHasNoErrors();

        $peticion = Http::recorded()[0][0];
        $this->assertSame($user->email, $peticion['to'][0]['email']);
        $this->assertSame(1, preg_match('/\b([0-9]{6})\b/', $peticion['textContent'], $coincidencias));
        $this->assertGuest();

        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => $coincidencias[1],
        ])->assertRedirect(route('contactos.index', absolute: false));

        $this->assertAuthenticatedAs($user);
        Http::assertSentCount(1);
    }

    public function test_a_brevo_rejection_does_not_leave_a_valid_email_code_or_authenticate_the_user(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 401)]);
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        $this->post(route('two-factor.send'))->assertRedirect(route('two-factor.show'))
            ->assertSessionHasErrors(['codigo' => 'No se pudo enviar el código. Puedes volver a intentarlo en un minuto.'])
            ->assertSessionMissing(SegundoFactor::DESAFIO.'.correo_firma');

        $this->assertGuest();
        Http::assertSentCount(1);
    }

    public function test_app_setup_expires_without_enabling_the_factor(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'preparar-app',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'));

        $this->travel(10)->minutes();
        $this->get(route('profile.edit'))->assertOk()->assertDontSee('QR para configurar la app autenticadora');
        $this->post(route('two-factor.confirm'), ['codigo' => '123456'])
            ->assertSessionHasErrorsIn('segundoFactor', ['accion' => 'Vuelve a iniciar la configuración de la app.']);

        $this->assertNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_an_unconfirmed_app_secret_does_not_count_as_an_enabled_factor(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => null,
        ]);

        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Configurar QR de autenticación');
    }

    public function test_email_challenge_blocks_private_pages_until_the_received_code_is_verified(): void
    {
        $user = User::factory()->create();
        $cookie = Auth::guard('web')->getRecallerName();

        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'));
        $user->refresh();
        $this->get(route('profile.edit'))->assertSee('Códigos de recuperación de un solo uso');

        $this->post('/logout');
        $this->get(route('contactos.index'))->assertRedirect(route('login'));
        $this->post('/login', [
            'login' => $user->indicativo,
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect(route('two-factor.show'))->assertCookieMissing($cookie);

        $this->assertGuest();
        $this->get(route('estadisticas'))->assertRedirect(route('login'));
        $this->get(route('two-factor.show'))->assertOk()->assertSee('Enviar un código a mi correo');

        $codigo = $this->codigoEnviado($user);
        $this->from(route('two-factor.show'))->post(route('two-factor.send'))->assertSessionHasErrors('codigo');

        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => '000000',
        ])->assertSessionHasErrors('codigo')
            ->assertSessionMissing('_old_input.codigo');
        $this->assertGuest();

        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => $codigo,
        ])->assertRedirect(route('estadisticas'))->assertCookie($cookie);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->iniciarSesion($user);
        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => $codigo,
        ])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_an_email_code_expires_while_the_login_challenge_is_still_pending(): void
    {
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        $codigo = $this->codigoEnviado($user);
        $this->travel(5)->minutes();

        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => $codigo,
        ])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    public function test_failed_attempts_are_limited_across_challenges_with_the_database_cache(): void
    {
        config(['cache.default' => 'database']);
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->post(route('two-factor.store'), [
                'metodo' => 'correo',
                'codigo' => '000000',
            ])->assertSessionHasErrors('codigo');
        }

        $this->iniciarSesion($user);
        $codigo = $this->codigoEnviado($user);
        $this->post(route('two-factor.store'), [
            'metodo' => 'correo',
            'codigo' => $codigo,
        ])->assertSessionHasErrors(['codigo' => 'Demasiados intentos. Espera cinco minutos.']);
        $this->assertGuest();
    }

    public function test_app_enrollment_requires_confirmation_and_recovery_codes_are_single_use(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.settings'), [
            'accion' => 'preparar-app',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'));

        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());
        $this->get(route('profile.edit'))->assertSee('QR para configurar la app autenticadora')
            ->assertHeader('Cache-Control', 'no-store, private');

        $secreto = Fortify::currentEncrypter()->decrypt(session(SegundoFactor::CONFIGURACION)['secreto']);
        $totp = new Google2FA;
        $intervalo = $totp->getTimestamp();

        $this->post(route('two-factor.confirm'), [
            'codigo' => $totp->oathTotp($secreto, $intervalo),
        ])->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
        $codigoRecuperacion = $user->recoveryCodes()[0];
        $this->get(route('profile.edit'))->assertSee($codigoRecuperacion);
        $this->get(route('profile.edit'))->assertDontSee($codigoRecuperacion);

        $this->post('/logout');
        $this->iniciarSesion($user);
        $this->get(route('two-factor.show'))->assertSee('App autenticadora')->assertDontSee('Enviar un código a mi correo');
        $this->post(route('two-factor.send'))->assertForbidden();
        $this->post(route('two-factor.store'), [
            'metodo' => 'app',
            'codigo' => $totp->oathTotp($secreto, $intervalo + 1),
        ])->assertRedirect(route('contactos.index', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->post(route('two-factor.settings'), [
            'accion' => 'activar-correo',
            'password' => 'password',
        ])->assertRedirect(route('profile.edit'));

        $this->post('/logout');
        $this->iniciarSesion($user);
        $this->get(route('two-factor.show'))->assertSee('App autenticadora')->assertSee('Enviar un código a mi correo');

        $this->post(route('two-factor.store'), [
            'metodo' => 'recuperacion',
            'codigo' => $codigoRecuperacion,
        ])->assertRedirect(route('contactos.index', absolute: false));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->iniciarSesion($user);
        $this->post(route('two-factor.store'), [
            'metodo' => 'recuperacion',
            'codigo' => $codigoRecuperacion,
        ])->assertSessionHasErrors('codigo');
        $this->assertGuest();
    }

    private function iniciarSesion(User $user): void
    {
        $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));
    }

    private function codigoEnviado(User $user): string
    {
        $this->from(route('two-factor.show'))->post(route('two-factor.send'))
            ->assertRedirect(route('two-factor.show'))->assertSessionHasNoErrors();

        $mensaje = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame($user->email, $mensaje->getTo()[0]->getAddress());
        $this->assertSame(1, preg_match('/\b([0-9]{6})\b/', $mensaje->getTextBody(), $coincidencias));

        return $coincidencias[1];
    }
}
