<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TarjetaQslMail extends Mailable
{
    public function __construct(
        public string $operador,
        public string $estacion,
        private string $nombreArchivo,
        private string $png,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Tarjeta QSL de {$this->operador} para {$this->estacion}");
    }

    public function content(): Content
    {
        return new Content(text: 'tarjeta-qsl.correo');
    }

    /** @return list<Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->png, $this->nombreArchivo)->withMime('image/png'),
        ];
    }
}
