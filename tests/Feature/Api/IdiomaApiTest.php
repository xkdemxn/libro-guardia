<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Finder\Finder;

/**
 * La API responde en el idioma que pide la SPA (Accept-Language): español, inglés o portugués.
 */
class IdiomaApiTest extends ApiTestCase
{
    use RefreshDatabase;

    #[TestWith(['es', 'Indicativo o contraseña incorrectos.'])]
    #[TestWith(['en', 'Incorrect callsign or password.'])]
    #[TestWith(['pt-BR,pt;q=0.9', 'Indicativo ou senha incorretos.'])]
    #[TestWith(['fr', 'Indicativo o contraseña incorrectos.'], 'un idioma que no tenemos cae al español')]
    public function test_messages_follow_the_requested_language(string $idioma, string $mensaje): void
    {
        $user = User::factory()->create();

        $this->withHeader('Accept-Language', $idioma)
            ->postJson(route('api.sesion.store'), ['login' => $user->email, 'password' => 'incorrecta'])
            ->assertJsonValidationErrors(['login' => $mensaje]);
    }

    public function test_laravel_own_messages_are_translated_too(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->postJson(route('api.registro.store'), [])
            ->assertJsonValidationErrors(['email' => 'The email field is required.']);
    }

    /**
     * Cada frase que el servidor pasa por __() tiene que estar traducida en lang/en.json y lang/pt_BR.json.
     */
    public function test_every_server_message_has_an_english_and_portuguese_translation(): void
    {
        $claves = [];
        foreach (Finder::create()->files()->in(app_path())->name('*.php') as $archivo) {
            preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)+)'/", $archivo->getContents(), $coincidencias);
            $claves = [...$claves, ...array_map('stripslashes', $coincidencias[1])];
        }

        foreach (['en', 'pt_BR'] as $idioma) {
            $traducciones = json_decode(file_get_contents(lang_path("{$idioma}.json")), true);
            $faltan = array_values(array_diff(array_unique($claves), array_keys($traducciones)));

            $this->assertSame([], $faltan, "Faltan traducciones en lang/{$idioma}.json");
        }
    }
}
