<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProbarCorreoTest extends TestCase
{
    public function test_test_email_is_sent_to_the_requested_recipient_through_brevo(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<prueba@brevo>'], 201)]);

        $this->artisan('correo:probar', ['destinatario' => 'prueba@example.com'])
            ->expectsOutputToContain('El proveedor aceptó el correo de prueba.')
            ->assertSuccessful();

        Http::assertSent(fn (Request $request): bool => $request['to'] === [['email' => 'prueba@example.com']]
            && $request['subject'] === 'Prueba de correo de Libro de Guardia');
    }

    public function test_test_email_rejects_an_invalid_recipient_without_sending(): void
    {
        Http::preventStrayRequests();

        $this->artisan('correo:probar', ['destinatario' => 'invalido'])
            ->expectsOutput('Ingresa una dirección de correo válida.')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_test_email_requires_a_configured_mail_service(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => '']);
        Http::preventStrayRequests();

        $this->artisan('correo:probar', ['destinatario' => 'prueba@example.com'])
            ->expectsOutputToContain('Configura MAIL_MAILER')->assertFailed();

        Http::assertNothingSent();
    }

    public function test_test_email_reports_a_provider_failure(): void
    {
        config(['mail.default' => 'brevo', 'services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 401)]);

        $this->artisan('correo:probar', ['destinatario' => 'prueba@example.com'])
            ->expectsOutputToContain('No se pudo enviar el correo.')->assertFailed();

        Http::assertSentCount(1);
    }
}
