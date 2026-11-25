<?php

namespace App\Console\Commands;

use App\Services\SegundoFactor;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[Signature('correo:probar {destinatario : Dirección que recibirá el mensaje de prueba}')]
#[Description('Envía un correo de prueba usando el servicio de correo configurado')]
class ProbarCorreo extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SegundoFactor $segundoFactor): int
    {
        $destinatario = $this->argument('destinatario');

        if (Validator::make(['email' => $destinatario], ['email' => ['required', 'email:rfc']])->fails()) {
            $this->error('Ingresa una dirección de correo válida.');

            return self::FAILURE;
        }

        if (! $segundoFactor->envioCorreoDisponible()) {
            $this->error('Configura MAIL_MAILER, MAIL_FROM_ADDRESS y las credenciales del proveedor antes de probar.');

            return self::FAILURE;
        }

        try {
            $enviado = Mail::raw('El servicio de correo de Libro de Guardia está listo para probar la verificación en dos pasos.', function (Message $mensaje) use ($destinatario): void {
                $mensaje->to($destinatario)->subject('Prueba de correo de Libro de Guardia');
            });
        } catch (TransportExceptionInterface $exception) {
            report($exception);
            $this->error('No se pudo enviar el correo. Revisa las credenciales, el remitente y los registros de la aplicación.');

            return self::FAILURE;
        }

        if ($enviado === null) {
            $this->error('El servicio de correo no aceptó el envío.');

            return self::FAILURE;
        }

        $this->info('El proveedor aceptó el correo de prueba. Revisa la bandeja de entrada y spam del destinatario.');

        return self::SUCCESS;
    }
}
