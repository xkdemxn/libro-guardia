<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Entrega de la SPA de React: compilada la sirve Laravel; en desarrollo se redirige a Vite.
 */
class SpaTest extends TestCase
{
    private string $indice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->indice = tempnam(sys_get_temp_dir(), 'spa');
        file_put_contents($this->indice, '<!doctype html><div id="raiz"></div>');
    }

    protected function tearDown(): void
    {
        @unlink($this->indice);

        parent::tearDown();
    }

    #[TestWith(['/'])]
    #[TestWith(['/contactos/12/editar'])]
    #[TestWith(['/indicativos/HC1QRC'])]
    public function test_the_compiled_spa_is_served_for_any_page(string $direccion): void
    {
        config(['app.spa_index' => $this->indice]);

        $this->get($direccion)
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
            ->assertSee('<div id="raiz"></div>', false);
    }

    public function test_api_addresses_are_never_answered_with_the_spa(): void
    {
        config(['app.spa_index' => $this->indice]);

        $this->getJson('/api/no-existe')->assertNotFound()->assertDontSee('raiz');
    }

    public function test_without_a_build_it_redirects_to_vite_keeping_the_address(): void
    {
        config(['app.spa_index' => '/no/existe/index.html', 'app.frontend_url' => 'http://localhost:5173']);

        $this->get('/contactos?page=2')->assertRedirect('http://localhost:5173/contactos?page=2');
    }
}
