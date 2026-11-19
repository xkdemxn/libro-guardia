<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

/**
 * Activar y desactivar la verificación en dos pasos desde «Mi perfil → Seguridad».
 */
class ConfiguracionSegundoFactorApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_every_change_requires_the_current_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'preparar-app', 'password' => 'incorrecta'])
            ->assertJsonValidationErrors('password');
    }

    public function test_the_app_is_enabled_after_confirming_a_code_and_shows_recovery_codes_once(): void
    {
        $user = User::factory()->create();

        $preparada = $this->actingAs($user)
            ->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'preparar-app', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('app', false)
            ->assertJsonPath('codigos_recuperacion', null);
        $this->assertStringContainsString('<svg', $preparada->json('qr_svg'));
        $this->assertFalse($user->fresh()->hasEnabledTwoFactorAuthentication());

        $this->postJson(route('api.segundo-factor.confirmar'), ['codigo' => '000000'])
            ->assertJsonValidationErrors(['codigo' => 'El código de la app es inválido.']);

        $codigo = (new Google2FA)->getCurrentOtp($preparada->json('clave_manual'));
        $activada = $this->postJson(route('api.segundo-factor.confirmar'), ['codigo' => $codigo])
            ->assertOk()
            ->assertJsonPath('app', true)
            ->assertJsonPath('qr_svg', null);

        $this->assertCount(8, $activada->json('codigos_recuperacion'));
        $this->assertTrue($user->fresh()->hasEnabledTwoFactorAuthentication());
    }

    public function test_confirming_without_preparing_the_app_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.segundo-factor.confirmar'), ['codigo' => '123456'])
            ->assertJsonValidationErrors(['codigo' => 'Vuelve a iniciar la configuración de la app.']);
    }

    public function test_the_email_factor_needs_a_working_mailer(): void
    {
        config(['mail.default' => 'log']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'activar-correo', 'password' => 'password'])
            ->assertJsonValidationErrors(['accion' => 'El envío de correo todavía no está configurado.']);

        $this->assertFalse($user->fresh()->two_factor_email_enabled);
    }

    public function test_turning_off_the_last_factor_removes_the_recovery_codes(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'activar-correo', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('correo', true)
            ->assertJsonCount(8, 'codigos_recuperacion');

        $this->postJson(route('api.segundo-factor.configuracion'), ['accion' => 'desactivar-correo', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('correo', false)
            ->assertJsonPath('codigos_recuperacion', null);

        $this->assertNull($user->fresh()->two_factor_recovery_codes);
    }
}
