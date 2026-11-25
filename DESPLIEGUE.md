# Despliegue de Libro de Guardia en Debian

Esta guía deja Libro de Guardia funcionando en un servidor Debian con HTTPS, en 10 pasos. Al final explica cómo actualizarlo y qué hacer si algo falla.

## Antes de empezar

El repositorio tiene dos carpetas: `backend/` (Laravel) y `frontend/` (React). La aplicación se instala completa en un único dominio: el frontend se compila dentro de `backend/public/spa`, y Laravel sirve tanto esa interfaz como la API en `/api`.

Necesitas:

- Un servidor con **Debian 13**, que trae PHP 8.4 en sus repositorios.
- Acceso por SSH con un usuario que tenga `sudo`.
- Un **dominio** cuyo registro DNS ya apunte a la IP del servidor.
- Una cuenta de **Gmail** con una contraseña de aplicación, o una clave de API de **Brevo**, para enviar correos.

En todos los comandos, cambia `tu-dominio.com`, `tu-correo@gmail.com` y `UNA_CLAVE_LARGA` por tus valores.

Si el servidor tiene **Debian 12**, PHP 8.4 no viene incluido y antes hay que agregar el repositorio de Sury.

## Paso 1. Instalar los paquetes del sistema

Se instalan el servidor web, PHP con sus extensiones, PostgreSQL, Composer, Node.js y Certbot.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql git unzip curl \
  php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd \
  composer nodejs npm certbot python3-certbot-nginx
```

Comprueba las versiones: `php -v` debe mostrar 8.4 y `node -v` 20.19 o superior.

## Paso 2. Crear la base de datos

Se crean un usuario y una base de datos de PostgreSQL solo para la aplicación.

```bash
sudo -u postgres psql -c "CREATE USER libro_guardia WITH PASSWORD 'UNA_CLAVE_LARGA';"
sudo -u postgres psql -c "CREATE DATABASE libro_guardia OWNER libro_guardia;"
```

Guarda esa contraseña: la necesitarás en el paso 4.

## Paso 3. Descargar el código

El código se clona desde GitHub en `/var/www/libro-guardia`.

```bash
sudo mkdir -p /var/www/libro-guardia
sudo chown $USER:www-data /var/www/libro-guardia
git clone https://github.com/xkdemxn/libro-guardia.git /var/www/libro-guardia
cd /var/www/libro-guardia/backend
```

Desde aquí, los comandos se ejecutan en `/var/www/libro-guardia/backend`, salvo los que indican otra carpeta.

Si el repositorio es privado, primero crea una *deploy key* en el servidor con `ssh-keygen -t ed25519`. Agrega el archivo `.pub` en GitHub (Settings → Deploy keys) y clona con `git@github.com:xkdemxn/libro-guardia.git`.

## Paso 4. Configurar el archivo .env

El archivo `backend/.env` guarda el dominio, la base de datos y el correo. Se crea a partir del ejemplo:

```bash
cp .env.example .env
nano .env
```

Cambia estas líneas:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
FRONTEND_URL=https://tu-dominio.com
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true

DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=libro_guardia
DB_USERNAME=libro_guardia
DB_PASSWORD=UNA_CLAVE_LARGA

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=tu-correo@gmail.com
MAIL_PASSWORD="xxxx xxxx xxxx xxxx"
MAIL_FROM_ADDRESS=tu-correo@gmail.com
```

Dos líneas se configuran mal con facilidad:

- **`APP_URL`** tiene que ser exactamente el dominio público, con `https`. Si no coincide, el registro y el inicio de sesión fallan.
- **`MAIL_PASSWORD`** va entre comillas, porque la contraseña de aplicación de Gmail lleva espacios. Sin comillas, Laravel no puede leer el archivo.

Si prefieres Brevo en lugar de Gmail, usa `MAIL_MAILER=brevo` y `BREVO_API_KEY=tu_clave_de_api`, con un remitente autorizado en Brevo.

## Paso 5. Instalar, migrar y compilar

Estos comandos instalan las dependencias, crean las tablas, cargan los catálogos y compilan la interfaz.

```bash
# Backend
cd /var/www/libro-guardia/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

# Frontend: compila la interfaz dentro de backend/public/spa
cd /var/www/libro-guardia/frontend
npm ci && npm run build

# De vuelta en el backend
cd /var/www/libro-guardia/backend
php artisan optimize
```

Qué hace cada uno:

- `key:generate` crea la clave de cifrado de la aplicación. Solo se ejecuta la primera vez.
- `db:seed` carga las bandas, los modos y los prefijos de país. En producción no crea las cuentas de demostración.
- `storage:link` hace visibles las fotos de perfil y de las tarjetas QSL.
- `npm run build` genera la interfaz en `backend/public/spa`. Comprueba que exista `backend/public/spa/index.html`.
- `optimize` guarda en caché la configuración y las rutas.

## Paso 6. Dar permisos

PHP necesita escribir en `backend/storage/` (registros, sesiones, imágenes) y en `backend/bootstrap/cache/`. El `.env` debe quedar protegido.

```bash
cd /var/www/libro-guardia/backend
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
chmod 640 .env && sudo chgrp www-data .env
```

## Paso 7. Ampliar el tamaño de subida en PHP

La aplicación acepta fotos de hasta 2 MB, portadas de hasta 4 MB y archivos ADIF de hasta 5 MB, pero PHP limita las subidas a 2 MB por defecto. Sin este paso, subir una portada falla.

```bash
sudo tee /etc/php/8.4/fpm/conf.d/99-libro-guardia.ini <<'EOF'
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
EOF
sudo systemctl restart php8.4-fpm
```

## Paso 8. Configurar Nginx

Nginx recibe las visitas y pasa a PHP todo lo que no sea un archivo estático. La raíz del sitio es la carpeta `backend/public/`; el resto del código queda fuera del alcance del navegador.

```bash
sudo tee /etc/nginx/sites-available/libro-guardia <<'EOF'
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/libro-guardia/backend/public;
    index index.php;
    client_max_body_size 12M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~* \.(js|css|svg|png|jpg|webp|woff2?)$ {
        expires 30d;
        access_log off;
        try_files $uri /index.php?$query_string;
    }

    location ~ /\.(?!well-known) { deny all; }
}
EOF
sudo ln -s /etc/nginx/sites-available/libro-guardia /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

`nginx -t` comprueba la configuración antes de recargarla. Si muestra un error, corrígelo antes de seguir.

## Paso 9. Activar HTTPS y el firewall

Certbot obtiene un certificado gratuito de Let's Encrypt y configura Nginx para usarlo. El certificado se renueva solo.

```bash
sudo certbot --nginx -d tu-dominio.com
```

Si usas el firewall `ufw`, abre SSH y los puertos web antes de activarlo, para no quedarte sin acceso:

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

## Paso 10. Comprobar que funciona

Primero desde la terminal:

```bash
curl -s https://tu-dominio.com/up
cd /var/www/libro-guardia/backend && php artisan correo:probar tu-correo@gmail.com
```

El primer comando debe responder `{"status":"up"}`. El segundo envía un correo real: revisa que llegue, también en spam.

Después, en el navegador:

- [ ] Crear una cuenta nueva
- [ ] Verificar el correo con el enlace recibido
- [ ] Registrar un contacto
- [ ] Subir una foto de perfil y una portada
- [ ] Cerrar sesión y volver a entrar

## Actualizar después de un cambio

Cuando se suben cambios a GitHub, en el servidor se ejecuta:

```bash
cd /var/www/libro-guardia/backend
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
(cd ../frontend && npm ci && npm run build)
php artisan optimize
php artisan up
```

`down` muestra una página de mantenimiento mientras dura la actualización, y `up` vuelve a abrir el sitio.

## Si algo falla

El error real siempre queda en el registro: `tail -n 50 /var/www/libro-guardia/backend/storage/logs/laravel.log`.

| Síntoma | Causa probable | Solución |
| --- | --- | --- |
| Error 500 al registrarse o iniciar sesión | `APP_URL` no coincide con el dominio o le falta `https` | Corregir `APP_URL` y ejecutar `php artisan optimize` |
| Error 419 (sesión expirada) | Lo mismo, o `SESSION_SECURE_COOKIE=true` sin HTTPS | Revisar `APP_URL` y completar el paso 9 |
| Error 413 al subir una imagen | Límite de subida de PHP o Nginx | Repetir el paso 7 y revisar `client_max_body_size` |
| Las fotos no se ven | Falta el enlace a `storage` | `php artisan storage:link` en `backend/` |
| Se ve "Página no encontrada" o redirige a `localhost:5173` | No se compiló el frontend | `npm ci && npm run build` en `frontend/` |
| Página en blanco o "Permission denied" | Permisos de carpetas | Repetir el paso 6 |
| "The environment file is invalid" | Un valor con espacios sin comillas en `.env` | Poner ese valor entre comillas |
| Cambié `.env` y no se nota | La configuración está en caché | `php artisan optimize` |
| No llegan los correos | Credenciales de Gmail o Brevo | `php artisan correo:probar` muestra el error |
