<?php

namespace Tests\Feature;

use App\Mail\TarjetaQslMail;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class BrevoMailTest extends TestCase
{
    public function test_brevo_sends_text_html_recipients_and_reply_to_through_the_api(): void
    {
        config(['services.brevo.key' => 'clave-de-prueba', 'mail.from.address' => 'acceso@example.com']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<prueba@brevo>'], 201)]);

        $enviado = Mail::mailer('brevo')->html('<p>Código 123456</p>', function (Message $mensaje): void {
            $mensaje->getSymfonyMessage()->text('Código 123456');
            $mensaje->to('destino@example.com', 'Operador')
                ->cc('copia@example.com')->bcc('oculta@example.com')
                ->replyTo('soporte@example.com', 'Soporte')->subject('Acceso');
        });

        $this->assertSame('prueba@brevo', $enviado->getMessageId());
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->hasHeader('api-key', 'clave-de-prueba')
            && $request['sender']['email'] === 'acceso@example.com'
            && $request['to'] === [['email' => 'destino@example.com', 'name' => 'Operador']]
            && $request['cc'] === [['email' => 'copia@example.com']]
            && $request['bcc'] === [['email' => 'oculta@example.com']]
            && $request['replyTo'] === ['email' => 'soporte@example.com', 'name' => 'Soporte']
            && $request['subject'] === 'Acceso'
            && $request['textContent'] === 'Código 123456'
            && $request['htmlContent'] === '<p>Código 123456</p>');
        Http::assertSentCount(1);
    }

    public function test_brevo_preserves_qsl_attachments(): void
    {
        config(['services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => '<qsl@brevo>'], 201)]);

        Mail::mailer('brevo')->to('destino@example.com')->send(new TarjetaQslMail('HC1QRC', 'LU4BB', 'tarjeta.png', 'contenido-png'));

        Http::assertSent(fn (Request $request): bool => $request['attachment'] === [
            ['name' => 'tarjeta.png', 'content' => 'Y29udGVuaWRvLXBuZw=='],
        ]);
    }

    #[DataProvider('rechazosBrevo')]
    public function test_brevo_reports_rejections_without_exposing_the_response_or_retrying(int $estado): void
    {
        config(['services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response(['message' => 'dato-sensible'], $estado)]);

        try {
            Mail::mailer('brevo')->raw('Código 123456', fn (Message $mensaje) => $mensaje->to('destino@example.com')->subject('Acceso'));
            $this->fail('El envío rechazado debe fallar.');
        } catch (TransportException $exception) {
            $this->assertSame('Brevo rechazó el envío (HTTP '.$estado.').', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public static function rechazosBrevo(): array
    {
        return ['credenciales' => [401], 'limite' => [429], 'servidor' => [500]];
    }

    public function test_brevo_reports_connection_failures_as_mail_transport_errors(): void
    {
        config(['services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::failedConnection()]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('No se pudo conectar con Brevo.');

        Mail::mailer('brevo')->raw('Código 123456', fn (Message $mensaje) => $mensaje->to('destino@example.com')->subject('Acceso'));
    }

    public function test_brevo_does_not_send_without_an_api_key(): void
    {
        config(['services.brevo.key' => '']);
        Http::preventStrayRequests();

        try {
            Mail::mailer('brevo')->raw('Código 123456', fn (Message $mensaje) => $mensaje->to('destino@example.com')->subject('Acceso'));
            $this->fail('No debe enviarse correo sin una clave de API.');
        } catch (TransportException $exception) {
            $this->assertSame('Configura BREVO_API_KEY antes de enviar correo.', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_brevo_requires_a_delivery_confirmation(): void
    {
        config(['services.brevo.key' => 'clave-de-prueba']);
        Http::preventStrayRequests();
        Http::fake(['https://api.brevo.com/v3/smtp/email' => Http::response([], 201)]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Brevo no devolvió la confirmación del envío.');

        Mail::mailer('brevo')->raw('Código 123456', fn (Message $mensaje) => $mensaje->to('destino@example.com')->subject('Acceso'));
    }
}
