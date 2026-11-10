<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombres' => 'Ana',
                'apellidos' => 'Pérez',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Ana', $user->nombres);
        $this->assertSame('Pérez', $user->apellidos);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'nombres' => 'Ana',
                'apellidos' => 'Pérez',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_station_details_and_visibility_can_be_updated(): void
    {
        $user = User::factory()->create(['indicativo' => 'HC1QRC']);

        $this->actingAs($user)
            ->patch('/profile', [
                'indicativo' => 'HC9XXX',
                'nombres' => 'Ana',
                'apellidos' => 'Pérez',
                'email' => $user->email,
                'ciudad' => 'Quito',
                'grid_locator' => 'fi09',
                'equipo_radio' => 'Yaesu FT-991A',
                'biografia' => 'Operadora desde 2010.',
                'mostrar_nombre' => '1',
                'mostrar_equipo' => '1',
            ])
            ->assertSessionHasNoErrors();

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
        $datos = ['nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => $user->email];

        $this->actingAs($user)->patch('/profile', [...$datos, 'foto' => UploadedFile::fake()->image('yo.jpg')]);
        $primeraFoto = $user->refresh()->foto_path;
        Storage::disk('public')->assertExists($primeraFoto);

        $this->actingAs($user)->patch('/profile', [...$datos, 'foto' => UploadedFile::fake()->image('nueva.png')]);
        Storage::disk('public')->assertMissing($primeraFoto);
        Storage::disk('public')->assertExists($user->refresh()->foto_path);
    }

    public function test_photos_with_wrong_format_or_too_heavy_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $datos = ['nombres' => 'Ana', 'apellidos' => 'Pérez', 'email' => $user->email];

        $this->actingAs($user)
            ->patch('/profile', [...$datos, 'foto' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors(['foto' => 'La foto debe ser JPG, PNG o WEBP.']);

        $this->actingAs($user)
            ->patch('/profile', [...$datos, 'foto' => UploadedFile::fake()->image('grande.jpg')->size(3000)])
            ->assertSessionHasErrors(['foto' => 'La foto no puede pesar más de 2 MB.']);

        $this->assertNull($user->refresh()->foto_path);
    }
}
