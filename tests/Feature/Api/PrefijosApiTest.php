<?php

namespace Tests\Feature\Api;

use Database\Seeders\PrefijoPaisSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrefijosApiTest extends ApiTestCase
{
    use RefreshDatabase;

    public function test_the_prefixes_are_listed_by_country_without_a_session(): void
    {
        $this->seed(PrefijoPaisSeeder::class);

        $paises = collect($this->getJson(route('api.prefijos'))->assertOk()->json('data'))->keyBy('pais');

        $this->assertSame(['HC', 'HD'], $paises['Ecuador']['prefijos']);
        $this->assertSame('ec', $paises['Ecuador']['codigo_bandera']);
        $this->assertSame('Sudamérica', $paises['Ecuador']['continente']);
        $this->assertSame(['HC8', 'HD8'], $paises['Islas Galápagos']['prefijos']);
    }
}
