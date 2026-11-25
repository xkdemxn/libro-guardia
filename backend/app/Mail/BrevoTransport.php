<?php

namespace App\Mail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
    public function __construct(#[\SensitiveParameter] private string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('Configura BREVO_API_KEY antes de enviar correo.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $datos = [
            'sender' => $this->direccion($email->getFrom()[0]),
            'subject' => $email->getSubject(),
        ];

        foreach (['to' => $email->getTo(), 'cc' => $email->getCc(), 'bcc' => $email->getBcc()] as $tipo => $direcciones) {
            if ($direcciones !== []) {
                $datos[$tipo] = array_map($this->direccion(...), $direcciones);
            }
        }

        if ($email->getReplyTo() !== []) {
            $datos['replyTo'] = $this->direccion($email->getReplyTo()[0]);
        }

        if ($email->getTextBody() !== null) {
            $datos['textContent'] = $email->getTextBody();
        }

        if ($email->getHtmlBody() !== null) {
            $datos['htmlContent'] = $email->getHtmlBody();
        }

        foreach ($email->getAttachments() as $adjunto) {
            $datos['attachment'][] = [
                'name' => $adjunto->getFilename(),
                'content' => base64_encode($adjunto->getBody()),
            ];
        }

        try {
            $respuesta = Http::acceptJson()
                ->withHeaders(['api-key' => $this->apiKey])
                ->connectTimeout(5)
                ->timeout(15)
                ->post('https://api.brevo.com/v3/smtp/email', $datos);
        } catch (ConnectionException) {
            throw new TransportException('No se pudo conectar con Brevo.');
        }

        // No incluir respuestas del proveedor: pueden contener destinatarios o contenido sensible.
        if (! $respuesta->successful()) {
            throw new TransportException('Brevo rechazó el envío (HTTP '.$respuesta->status().').');
        }

        $id = $respuesta->json('messageId');
        if (! is_string($id) || $id === '') {
            throw new TransportException('Brevo no devolvió la confirmación del envío.');
        }

        $message->setMessageId(trim($id, '<>'));
    }

    /** @return array{email: string, name?: string} */
    private function direccion(Address $direccion): array
    {
        return array_filter([
            'email' => $direccion->getAddress(),
            'name' => $direccion->getName(),
        ], fn (string $valor): bool => $valor !== '');
    }

    public function __toString(): string
    {
        return 'brevo';
    }
}
