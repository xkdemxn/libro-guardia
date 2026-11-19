<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/**
 * «Mi perfil» desde la SPA (US-13): datos, imágenes, ubicación, privacidad, contraseña y cuenta.
 */
class PerfilApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_the_profile_requires_a_session(): void
    {
        $this->getJson(route('api.perfil.show'))->assertUnauthorized();
        $this->patchJson(route('api.perfil.update'), ['nombres' => 'Ana'])->assertUnauthorized();
    }

    public function test_the_profile_returns_the_editable_data_and_the_visibility(): void
    {
        $user = User::factory()->create(['grid_locator' => 'FI09']);

        $this->actingAs($user)->getJson(route('api.perfil.show'))
            ->assertOk()
            ->assertJsonPath('data.grid_locator', 'FI09')
            ->assertJsonPath('data.visibilidad.mostrar_equipo', false)
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_changing_the_email_marks_it_as_unverified(): void
    {
        $user = User::factory()->create();

        $this->actualizar($user, ['email' => 'nuevo@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'nuevo@example.com')
            ->assertJsonPath('data.correo_verificado', false);

        $this->assertNull($user->refresh()->email_verified_at);
    }

    public function test_keeping_the_same_email_keeps_it_verified(): void
    {
        $user = User::factory()->create();

        $this->actualizar($user, ['nombres' => 'Ana'])->assertOk();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_the_email_cannot_change_while_the_email_factor_is_on(): void
    {
        $user = User::factory()->create(['two_factor_email_enabled' => true]);

        $this->actualizar($user, ['email' => 'nuevo@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'Desactiva la verificación por correo en Seguridad antes de cambiar tu dirección.']);
    }

    public function test_station_details_and_visibility_can_be_updated_but_not_the_callsign(): void
    {
        $this->seed(PrefijoPaisSeeder::class);
        $user = User::factory()->create(['indicativo' => 'HC1QRC']);

        $this->actualizar($user, [
            'indicativo' => 'HC9XXX',
            'ciudad' => 'Quito',
            'grid_locator' => 'fi09',
            'equipo_radio' => 'Yaesu FT-991A',
            'mostrar_nombre' => true,
            'mostrar_equipo' => true,
        ])->assertOk();

        $user->refresh();
        $this->assertSame('HC1QRC', $user->indicativo);
        $this->assertSame('FI09', $user->grid_locator);
        $this->assertSame('Yaesu FT-991A', $user->equipo_radio);
        $this->assertTrue($user->visibilidad->mostrar_nombre);
        $this->assertTrue($user->visibilidad->mostrar_equipo);
        $this->assertFalse($user->visibilidad->mostrar_pais_ciudad);
        $this->assertFalse($user->visibilidad->mostrar_biografia_foto);
    }

    public function test_a_profile_photo_can_be_uploaded_and_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actualizar($user, ['foto' => UploadedFile::fake()->image('yo.jpg')])->assertOk()->assertJsonPath('data.tiene_foto', true);
        $primeraFoto = $user->refresh()->foto_path;
        Storage::disk('public')->assertExists($primeraFoto);

        $this->actualizar($user, ['foto' => UploadedFile::fake()->image('nueva.png')])->assertOk();
        Storage::disk('public')->assertMissing($primeraFoto);
        Storage::disk('public')->assertExists($user->refresh()->foto_path);
    }

    public function test_photos_with_wrong_format_or_too_heavy_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actualizar($user, ['foto' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertJsonValidationErrors(['foto' => 'La foto debe ser JPG, PNG o WEBP.']);
        $this->actualizar($user, ['foto' => UploadedFile::fake()->image('grande.jpg')->size(3000)])
            ->assertJsonValidationErrors(['foto' => 'La foto no puede pesar más de 2 MB.']);

        $this->assertNull($user->refresh()->foto_path);
    }

    public function test_the_cover_is_saved_with_its_style_and_can_be_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actualizar($user, ['portada' => UploadedFile::fake()->image('cerro.jpg', 1600, 900), 'portada_estilo' => 'completa', 'portada_opacidad' => 40])
            ->assertOk()
            ->assertJsonPath('data.portada.estilo', 'completa')
            ->assertJsonPath('data.portada.opacidad', 40);
        $portada = $user->refresh()->portada_path;
        Storage::disk('public')->assertExists($portada);

        $this->actualizar($user, ['quitar_portada' => true])->assertOk()->assertJsonPath('data.portada.url', null);
        Storage::disk('public')->assertMissing($portada);
    }

    public function test_the_cover_opacity_cannot_exceed_50_percent(): void
    {
        $this->actualizar(User::factory()->create(), ['portada_estilo' => 'completa', 'portada_opacidad' => 80])
            ->assertJsonValidationErrors(['portada_opacidad' => 'La opacidad debe estar entre 10 % y 50 %.']);
    }

    public function test_an_ecuadorian_city_must_belong_to_the_chosen_province(): void
    {
        $user = User::factory()->create(['indicativo' => 'HC6TVB']);

        $this->actualizar($user, ['pais' => 'Ecuador', 'provincia' => 'Bolívar', 'ciudad' => 'Guaranda'])->assertOk();
        $this->assertSame(['Ecuador', 'Bolívar', 'Guaranda'], [$user->refresh()->pais, $user->provincia, $user->ciudad]);

        $this->actualizar($user, ['pais' => 'Ecuador', 'provincia' => 'Bolívar', 'ciudad' => 'Quito'])
            ->assertJsonValidationErrors(['ciudad' => 'Elige una ciudad de la lista.']);
    }

    public function test_without_a_province_it_takes_the_one_of_the_canton(): void
    {
        $user = User::factory()->create(['indicativo' => 'HC2AA']);

        $this->actualizar($user, ['pais' => 'Ecuador', 'ciudad' => 'Machala'])->assertOk();

        $this->assertSame('El Oro', $user->refresh()->provincia);
    }

    public function test_country_and_city_must_come_from_the_lists(): void
    {
        $user = User::factory()->create(['indicativo' => 'LU4BB']);

        $this->actualizar($user, ['pais' => 'Narnia', 'ciudad' => 'Cair Paravel'])
            ->assertJsonValidationErrors(['pais' => 'Elige un país de la lista.', 'ciudad' => 'Elige una ciudad de la lista.']);

        $this->actualizar($user, ['pais' => 'Argentina', 'provincia' => 'Azuay', 'ciudad' => 'Rosario'])->assertOk();
        $this->assertSame(['Argentina', null, 'Rosario'], [$user->refresh()->pais, $user->provincia, $user->ciudad]);
    }

    public function test_a_city_written_before_the_lists_existed_is_kept(): void
    {
        $user = User::factory()->create(['pais' => 'Argentina', 'ciudad' => 'Villa Gesell']);

        $this->actualizar($user, ['pais' => 'Argentina', 'ciudad' => 'Villa Gesell'])->assertOk()->assertJsonPath('data.ciudad', 'Villa Gesell');
    }

    public function test_the_location_lists_are_public(): void
    {
        $this->getJson(route('api.ubicaciones'))
            ->assertOk()
            ->assertJsonFragment(['Ecuador'])
            ->assertJsonPath('ecuador.El Oro', fn (array $cantones): bool => in_array('Machala', $cantones, true));
    }

    public function test_the_password_changes_only_with_the_current_one(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('api.contrasena.update'), [
            'current_password' => 'incorrecta',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertJsonValidationErrors('current_password');

        $this->putJson(route('api.contrasena.update'), [
            'current_password' => 'password',
            'password' => 'nueva1234',
            'password_confirmation' => 'nueva1234',
        ])->assertOk();

        $this->assertTrue(Hash::check('nueva1234', $user->refresh()->password));
    }

    public function test_the_account_is_deleted_only_with_the_correct_password(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actualizar($user, ['foto' => UploadedFile::fake()->image('yo.jpg')]);
        $foto = $user->refresh()->foto_path;

        $this->actingAs($user)->deleteJson(route('api.perfil.destroy'), ['password' => 'incorrecta'])
            ->assertJsonValidationErrors('password');
        $this->assertNotNull($user->fresh());

        $this->deleteJson(route('api.perfil.destroy'), ['password' => 'password'])->assertNoContent();

        $this->assertGuest('web');
        $this->assertNull($user->fresh());
        Storage::disk('public')->assertMissing($foto);
    }

    /**
     * Envía el formulario completo como lo hace la SPA (multipart con _method=PATCH).
     *
     * @param  array<string, mixed>  $cambios
     */
    private function actualizar(User $user, array $cambios): TestResponse
    {
        $user->refresh();

        return $this->actingAs($user)->post(route('api.perfil.update'), [
            '_method' => 'PATCH',
            'nombres' => $user->nombres,
            'apellidos' => $user->apellidos,
            'email' => $user->email,
            ...array_map(fn (mixed $valor): mixed => is_bool($valor) ? ($valor ? '1' : '0') : $valor, $cambios),
        ], ['Accept' => 'application/json']);
    }
}
