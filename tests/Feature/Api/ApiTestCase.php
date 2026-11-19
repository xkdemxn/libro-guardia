<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Base de los tests de la API de la SPA: las peticiones llegan desde el origen de la SPA,
 * que Sanctum trata como "stateful" (sesión por cookie).
 */
abstract class ApiTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['sanctum.stateful' => ['localhost:5173']]);
        $this->withHeader('Origin', 'http://localhost:5173');
    }
}
