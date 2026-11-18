# Libro de Guardia

Aplicación Laravel para registrar contactos de radioafición, gestionar confirmaciones QSL, importar y exportar ADIF y consultar estadísticas.

## Preparación local

Requiere PHP 8.4 con las extensiones de Composer (incluidas `bcmath` y `pdo_pgsql`), Composer, PostgreSQL y Node.js 22 o superior.

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Configura `APP_URL` y las variables `DB_*` de `.env` con tu base PostgreSQL. Después:

```sh
php artisan migrate
npm ci
npm run build
php artisan serve
```

Para cargar los catálogos y los operadores de demostración en una base local vacía, ejecuta `php artisan db:seed`. Las cuentas de demostración son `HC1QRC` y `LU4BB`, con contraseña `password`; no las cargues en producción.

## Autenticación con QR

1. Inicia sesión y abre **Mi perfil → Seguridad**.
2. Escribe tu contraseña actual y pulsa **Configurar QR de autenticación**.
3. Escanea el QR con tu app autenticadora e introduce su código de seis dígitos para activar el método. El QR pendiente vence en diez minutos.
4. Guarda los códigos de recuperación: se muestran una sola vez y cada uno sirve para un único acceso.
5. Cierra sesión y vuelve a entrar. Tras la contraseña, introduce el código de la app.

Cada operador activa su propio QR. No se activa el segundo factor hasta confirmar el código; generar el QR no cambia por sí solo el acceso a la cuenta. Si acabas de usar un código, espera al siguiente antes de probar otro acceso.

## Correo mediante la API de Brevo

El transporte `brevo` usa HTTPS y la [API de correo transaccional](https://developers.brevo.com/reference/send-transac-email). También sirve para verificar direcciones, restablecer contraseñas y enviar tarjetas QSL con adjuntos.

Autoriza el remitente en Brevo y configura `.env`:

```dotenv
MAIL_MAILER=brevo
BREVO_API_KEY=tu_clave_de_api
MAIL_FROM_ADDRESS=remitente@tu-dominio.com
MAIL_FROM_NAME="Libro de Guardia"
```

`BREVO_API_KEY` debe ser una clave de API, distinta de una clave SMTP. No guardes credenciales en Git. Ajusta `APP_URL` a la dirección desde la que abrirás los enlaces de verificación.

```sh
php artisan config:clear
php artisan correo:probar tu-correo@tu-dominio.com
```

Este comando envía un mensaje real al destinatario indicado y devuelve un error si el proveedor rechaza el envío. Que Brevo lo acepte no garantiza la llegada a la bandeja de entrada: revisa también spam y los registros de entrega de Brevo.

Para probar el segundo factor, verifica primero el correo de tu cuenta desde la aplicación. Luego entra en **Mi perfil → Seguridad**, confirma tu contraseña y pulsa **Activar correo**. Al volver a iniciar sesión, pide el código y úsalo antes de cinco minutos. Puedes pedir otro código después de un minuto; el nuevo reemplaza al anterior. El desafío completo de acceso vence en diez minutos.

Con `MAIL_MAILER=log` el segundo factor por correo permanece deshabilitado. Con Brevo también requiere una clave de API y una dirección de remitente válida. La configuración no comprueba las credenciales con el proveedor hasta intentar un envío.

## Pruebas

Crea una base PostgreSQL independiente llamada `libro_guardia_test`, accesible con las variables `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` del entorno local. La suite recrea sus tablas; no uses una base con datos que necesites conservar.

```sh
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

Las pruebas de Brevo simulan las respuestas HTTP y no envían mensajes reales. Cubren el transporte, los adjuntos QSL, el comando de prueba, el desafío de acceso y los fallos de entrega.
