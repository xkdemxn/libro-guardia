<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\SegundoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

/**
 * Segundo paso del inicio de sesión desde la SPA: código por correo, de la app o de recuperación.
 */
class DesafioSegundoFactorApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_the_email_code_is_sent_through_brevo_and_completes_the_login(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<acceso@brevo>'], 201)]);
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        $this->postJson(route('api.segundo-factor.correo'))->assertAccepted();

        $peticion = Http::recorded()[0][0];
        $this->assertSame($user->email, $peticion['to'][0]['email']);
        $this->assertSame(1, preg_match('/\b([0-9]{6})\b/', $peticion['textContent'], $coincidencias));
        $this->getJson(route('api.segundo-factor.show'))->assertJsonPath('metodos.correo_enviado', true);
        $this->assertGuest('web');

        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => $coincidencias[1]])->assertOk();

        $this->assertAuthenticatedAs($user, 'web');
        Http::assertSentCount(1);
    }

    public function test_a_brevo_rejection_does_not_leave_a_valid_email_code(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 401)]);
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        $this->postJson(route('api.segundo-factor.correo'))
            ->assertJsonValidationErrors(['codigo' => 'No se pudo enviar el código. Puedes volver a intentarlo en un minuto.'])
            ->assertSessionMissing(SegundoFactor::DESAFIO.'.correo_firma');

        $this->assertGuest('web');
    }

    public function test_the_email_code_is_single_use_and_another_cannot_be_requested_within_a_minute(): void
    {
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $cookie = Auth::guard('web')->getRecallerName();
        $this->iniciarSesion($user, recordar: true);

        $codigo = $this->codigoEnviado($user);
        $this->postJson(route('api.segundo-factor.correo'))->assertJsonValidationErrors(['codigo' => 'Espera un minuto antes de pedir otro código.']);

        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => '000000'])->assertJsonValidationErrors('codigo');
        $this->getJson(route('api.usuario'))->assertUnauthorized();

        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => $codigo])->assertOk()->assertCookie($cookie);
        $this->assertAuthenticatedAs($user, 'web');

        $this->cerrarSesion();
        $this->iniciarSesion($user);
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => $codigo])->assertJsonValidationErrors('codigo');
        $this->assertGuest('web');
    }

    public function test_an_email_code_expires_after_five_minutes(): void
    {
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);
        $codigo = $this->codigoEnviado($user);

        $this->travel(5)->minutes();

        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => $codigo])->assertJsonValidationErrors('codigo');
        $this->assertGuest('web');
    }

    public function test_failed_attempts_are_limited_across_challenges(): void
    {
        config(['cache.default' => 'database']);
        $user = User::factory()->create(['two_factor_email_enabled' => true]);
        $this->iniciarSesion($user);

        for ($intento = 0; $intento < 5; $intento++) {
            $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => '000000'])->assertJsonValidationErrors('codigo');
        }

        $this->iniciarSesion($user);
        $codigo = $this->codigoEnviado($user);
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'correo', 'codigo' => $codigo])
            ->assertJsonValidationErrors(['codigo' => 'Demasiados intentos. Espera cinco minutos.']);
        $this->assertGuest('web');
    }

    public function test_without_the_email_factor_no_code_can_be_requested(): void
    {
        $secreto = 'JBSWY3DPEHPK3PXP';
        $user = User::factory()->create([
            'two_factor_secret' => encrypt($secreto),
            'two_factor_confirmed_at' => now(),
        ]);
        $this->iniciarSesion($user);

        $this->getJson(route('api.segundo-factor.show'))->assertJsonPath('metodos.app', true)->assertJsonPath('metodos.correo', false);
        $this->postJson(route('api.segundo-factor.correo'))->assertForbidden();
    }

    public function test_recovery_codes_are_single_use(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $preparada = $this->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'preparar-app', 'password' => 'password']);
        $codigos = $this->postJson(route('api.segundo-factor.confirmar'), [
            'codigo' => (new Google2FA)->getCurrentOtp($preparada->json('clave_manual')),
        ])->json('codigos_recuperacion');
        $this->cerrarSesion();

        $this->iniciarSesion($user->fresh());
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'recuperacion', 'codigo' => $codigos[0]])->assertOk();
        $this->assertAuthenticatedAs($user, 'web');

        $this->cerrarSesion();
        $this->iniciarSesion($user->fresh());
        $this->postJson(route('api.segundo-factor.store'), ['metodo' => 'recuperacion', 'codigo' => $codigos[0]])->assertJsonValidationErrors('codigo');
        $this->assertGuest('web');
    }

    public function test_an_unfinished_app_setup_expires_without_enabling_the_factor(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'preparar-app', 'password' => 'password'])->assertOk();

        $this->travel(10)->minutes();

        $this->postJson(route('api.segundo-factor.confirmar'), ['codigo' => '123456'])
            ->assertJsonValidationErrors(['codigo' => 'Vuelve a iniciar la configuración de la app.']);
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    /**
     * En los tests la aplicación no se reinicia entre peticiones: el guard de Sanctum recordaría al usuario.
     */
    private function cerrarSesion(): void
    {
        $this->deleteJson(route('api.sesion.destroy'))->assertNoContent();
        Auth::forgetGuards();
    }

    private function iniciarSesion(User $user, bool $recordar = false): void
    {
        $this->postJson(route('api.sesion.store'), [
            'login' => $user->email,
            'password' => 'password',
            'remember' => $recordar,
        ])->assertOk()->assertJsonPath('segundo_factor', true);
    }

    private function codigoEnviado(User $user): string
    {
        $this->postJson(route('api.segundo-factor.correo'))->assertAccepted();

        $mensaje = Mail::mailer('array')->getSymfonyTransport()->messages()->last()->getOriginalMessage();
        $this->assertSame($user->email, $mensaje->getTo()[0]->getAddress());
        $this->assertSame(1, preg_match('/\b([0-9]{6})\b/', $mensaje->getTextBody(), $coincidencias));

        return $coincidencias[1];
    }
}
