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

## Correo mediante Gmail SMTP

Si la cuenta usa Gmail o Google Workspace y permite contraseñas de aplicación, configura `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=tu-correo@tu-dominio.com
MAIL_PASSWORD="tu_contraseña_de_aplicacion"
MAIL_FROM_ADDRESS=tu-correo@tu-dominio.com
MAIL_FROM_NAME="Libro de Guardia"
```

Activa la verificación en dos pasos de Google y genera una contraseña para «Libro de Guardia» en [Contraseñas de aplicación](https://myaccount.google.com/apppasswords). Añádela directamente en `MAIL_PASSWORD` de tu `.env` local. No uses la contraseña habitual de la cuenta. Las cuentas institucionales pueden tener esta opción restringida por su administrador; consulta la [ayuda de Google](https://support.google.com/accounts/answer/185833?hl=es).

```sh
php artisan config:clear
php artisan correo:probar tu-correo@tu-dominio.com
```

El comando enviará un correo real al destinatario indicado. Con Gmail, el segundo factor por correo no se puede activar mientras falten el usuario o la contraseña de aplicación. Una vez comprobada la entrega, verifica el correo de tu cuenta en la aplicación y activa el método desde **Mi perfil → Seguridad**.

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

## Producción

Requiere PHP 8.4 con `pdo_pgsql`, PostgreSQL y Node.js 22 o superior (solo para compilar). En el `.env` del servidor:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
FRONTEND_URL=https://tu-dominio.com
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
```

Configura también las variables `DB_*` y el correo (Gmail o Brevo, ver arriba). Si una contraseña tiene espacios, escríbela entre comillas. El dominio de `APP_URL` es el que acepta la sesión de la SPA: debe coincidir exactamente con la dirección pública.

```sh
composer install --no-dev --optimize-autoloader
php artisan key:generate        # solo la primera vez
php artisan migrate --force
php artisan db:seed --force     # carga bandas, modos y prefijos; en producción no crea cuentas de demostración
php artisan storage:link
npm ci && npm run build
php artisan optimize
```

El servidor web debe apuntar a la carpeta `public/`, y PHP necesita permiso de escritura en `storage/` y `bootstrap/cache/`. Después de cada actualización, repite `composer install`, `migrate --force`, `npm run build` y `php artisan optimize`.
