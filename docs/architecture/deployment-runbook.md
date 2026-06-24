# Guía de Despliegue — CienciasNET (Colegio Ciencias)

> **Runbook detallado de referencia.** Los comandos deben validarse contra el código y versiones reales antes de ejecutar
> en producción. Los principios obligatorios están resumidos en `deployment.md`.

Dos opciones de despliegue:

- **Opción A: Instalación Manual** — Control total sobre cada componente. Requiere instalar PHP, PostgreSQL, Nginx y
  Node.js manualmente.
- **Opción B: Despliegue con Docker** — Un solo comando. Todo empaquetado en contenedores. Más rápido, limpio y
  reproducible.

---

## Servidor Recomendado

| Plan             | vCPU | RAM  | Disco     | Precio/mes |
|------------------|------|------|-----------|------------|
| **Hetzner CX32** | 4    | 8 GB | 80 GB SSD | ~$10       |

Ubuntu 22.04 LTS. Suficiente para Laravel + React (Vite) + PostgreSQL para el colegio completo.

El mismo VPS puede ejecutar inicialmente el servicio facial Python para varios dispositivos que envían capturas
puntuales. Antes del despliegue definitivo se debe realizar una prueba en hora de ingreso con la concurrencia esperada y
medir CPU, memoria y latencia. Si afecta a Laravel o no procesa el flujo esperado, el servicio facial se mueve a un VPS
separado sin cambiar su contrato con Laravel.

El servicio facial, sus credenciales técnicas y R2 forman parte del despliegue inicial obligatorio. Aunque el repositorio
actual todavía sea un scaffold documental, no se considera completa la primera versión sin estos componentes.

---

# Opción A: Instalación Manual

## A.1. Preparación

```bash
ssh root@IP_DEL_VPS
apt update && apt upgrade -y
adduser cienciasnet
usermod -aG sudo cienciasnet
```

---

## A.2. Instalar Nginx

```bash
apt install nginx -y
systemctl enable nginx && systemctl start nginx
```

---

## A.3. Instalar PHP 8.3 + extensiones

```bash
add-apt-repository ppa:ondrej/php -y && apt update
apt install php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath \
  php8.3-gd php8.3-imagick -y

php -v
```

---

## A.4. Instalar PostgreSQL 16

```bash
apt install -y postgresql-common
/usr/share/postgresql-common/pgdg/apt.postgresql.org.sh
apt install -y postgresql-16
systemctl enable postgresql && systemctl start postgresql

sudo -u postgres psql -c "CREATE DATABASE cienciasnet;"
sudo -u postgres psql -c "CREATE USER cienciasnet_user WITH ENCRYPTED PASSWORD 'contraseña_muy_segura';"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE cienciasnet TO cienciasnet_user;"
sudo -u postgres psql -c "ALTER DATABASE cienciasnet OWNER TO cienciasnet_user;"
```

---

## A.5. Instalar Composer y Node.js

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
```

---

## A.6. Configurar Nginx

`/etc/nginx/sites-available/cienciasnet`:

```nginx
# Backend Laravel API
server {
    listen 443 ssl http2;
    server_name api.cienciascolegio.pe;

    root /var/www/CienciasNET/backend/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/api.cienciascolegio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.cienciascolegio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ^~ /storage/ {
        deny all;
    }

    location ~ /\.ht { deny all; }
}

# Frontend React (estáticos)
server {
    listen 443 ssl http2;
    server_name cienciascolegio.pe;

    root /var/www/CienciasNET/frontend/dist;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/cienciascolegio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cienciascolegio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }
}

server {
    listen 80;
    server_name cienciascolegio.pe api.cienciascolegio.pe;
    return 301 https://$host$request_uri;
}
```

```bash
ln -s /etc/nginx/sites-available/cienciasnet /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## A.7. SSL con Let's Encrypt

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d cienciascolegio.pe -d api.cienciascolegio.pe
```

---

## A.8. Deploy del Backend Laravel

```bash
cd /var/www/CienciasNET/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env

php artisan migrate --force
php artisan db:seed --force

mkdir -p storage/app/private/fotos
mkdir -p storage/app/private/separatas
mkdir -p storage/app/private/comprobantes
mkdir -p storage/app/private/temp

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## A.9. Deploy del Frontend React + Vite

```bash
cd /var/www/CienciasNET/frontend
npm install
cp .env.example .env
nano .env  # VITE_API_URL=https://api.cienciascolegio.pe
npm run build
chown -R www-data:www-data dist/
```

---

## A.9.1. Deploy del Servicio Facial Python

El servicio facial se ejecuta como proceso independiente y no debe exponerse directamente a Internet. Las estaciones web
envían capturas al endpoint HTTPS de Laravel; Laravel se comunica internamente con el servicio facial.

```bash
cd /var/www/CienciasNET/facial-service
python3 -m venv .venv
. .venv/bin/activate
pip install -r requirements.txt
```

Crear una unidad `systemd` que ejecute FastAPI/Uvicorn con usuario sin privilegios, reinicio automático y variables
secretas fuera del repositorio. Laravel envía las capturas al servicio por la red privada y recibe como respuesta la
identificación, confianza y prueba de vida; el servicio no expone endpoints al navegador.

---

## A.10. CI/CD con GitHub Actions

`.github/workflows/deploy.yml`:

```yaml
name: Deploy CienciasNET

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            echo "=== Backend ==="
            cd /var/www/CienciasNET/backend
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache && php artisan route:cache

            echo "=== Frontend ==="
            cd /var/www/CienciasNET/frontend
            git pull origin main
            npm install && npm run build
```

---

## A.11. Política de Backups

- Alcance obligatorio: PostgreSQL, `storage/app/private`, objetos/inventario de R2 y copia custodiada de las claves
  necesarias para restaurar datos cifrados.
- Los respaldos se cifran antes de enviarse a un almacenamiento externo al VPS.
- Retención inicial: 30 respaldos diarios y 12 mensuales.
- Objetivos iniciales: RPO máximo de 24 horas y RTO máximo de 4 horas.
- Se ejecuta una restauración de prueba trimestral en un entorno aislado y se documenta resultado, duración y faltantes.
- Las credenciales y claves nunca se incluyen en el repositorio ni dentro del mismo respaldo sin cifrado independiente.
- Una tarea diaria genera y verifica checksums; otra replica fuera del VPS y alerta si falla.

---

## A.12. Limpieza de Archivos Temporales

```bash
0 9 * * * find /var/www/CienciasNET/backend/storage/app/private/temp/ -mtime +1 -delete
```

---

## A.13. Comandos Útiles (Manual)

```bash
tail -f /var/www/CienciasNET/backend/storage/logs/laravel.log
systemctl status nginx
systemctl restart php8.3-fpm
sudo -u postgres psql cienciasnet
df -h /var/www/CienciasNET/backend/storage/
du -sh /var/www/CienciasNET/backend/storage/app/private/*/
```

---

# Opción B: Despliegue con Docker

## B.1. Requisitos en el VPS

```bash
ssh root@IP_DEL_VPS
apt update && apt upgrade -y
adduser cienciasnet
usermod -aG sudo cienciasnet

apt install nginx certbot python3-certbot-nginx -y
systemctl enable nginx && systemctl start nginx

curl -fsSL https://get.docker.com | bash
apt install docker-compose-plugin -y
usermod -aG docker cienciasnet

docker --version
docker compose version
```

---

## B.2. Estructura de Archivos Docker

```
CienciasNET/
├── docker-compose.yml          # Orquesta los servicios
├── .env                        # Variables de entorno (copiar de .env.docker.example)
├── backend/
│   ├── Dockerfile              # PHP 8.3-FPM + extensiones + Composer
│   └── entrypoint.sh           # Espera BD → migraciones → seeders → inicia
├── facial-service/
│   ├── Dockerfile              # Python + API facial
│   └── requirements.txt
└── frontend/
    ├── Dockerfile              # Multi-stage: node build → nginx serve
    └── nginx.conf              # SPA routing + proxy /api/ → backend
```

### Servicios

| Servicio   | Imagen/Base          | Rol                                            |
|------------|----------------------|------------------------------------------------|
| `db`       | `postgres:16-alpine` | PostgreSQL con volume `pgdata`                 |
| `backend`  | `php:8.3-fpm-alpine` | Laravel API. Volume `storage` para archivos    |
| `queue`    | Mismo que backend    | Colas Laravel (emails)                         |
| `facial-api` | Python / FastAPI   | Reconocimiento facial; sin reglas de asistencia |
| `frontend` | `nginx:alpine`       | Sirve build estático. Proxy `/api/` al backend |

`facial-api` comparte una red Docker privada con Laravel. Nginx expone únicamente el endpoint HTTPS de Laravel requerido
por las estaciones web, protegido con sesiones técnicas limitadas y rate limiting; `facial-api` permanece disponible
solo dentro de la red privada.

---

## B.3. Variables de Entorno

```bash
cd /var/www/CienciasNET
cp .env.docker.example .env
nano .env
```

Variables clave a configurar:

| Variable        | Valor por defecto                | Notas                             |
|-----------------|----------------------------------|-----------------------------------|
| `DB_PASSWORD`   | `change_me`                      | **Cambiar** por contraseña segura |
| `VITE_API_URL`  | `https://api.cienciascolegio.pe` | URL de la API                     |
| `FRONTEND_PORT` | `8080`                           | Puerto interno para Nginx host    |
| `MAIL_HOST`     | `smtp.gmail.com`                 | Servidor SMTP                     |
| `MAIL_USERNAME` | —                                | Credenciales de correo            |
| `MAIL_PASSWORD` | —                                | Password de aplicación            |
| `FACIAL_SERVICE_TOKEN` | —                         | Token largo para comunicación interna |
| `BIOMETRIC_ENCRYPTION_KEY` | —                    | Clave independiente para embeddings |
| `R2_ENDPOINT` | —                                 | Endpoint S3 compatible de R2       |
| `R2_BUCKET_BIOMETRICS` | `cienciasnet-biometria` | Bucket privado                     |
| `R2_ACCESS_KEY_ID` | —                            | Credencial limitada al bucket      |
| `R2_SECRET_ACCESS_KEY` | —                        | Secreto limitado al bucket         |

---

### Configuración de Correo con Gmail

El colegio usa su cuenta Gmail existente para las notificaciones a padres. Gmail permite enviar correos desde
aplicaciones externas mediante SMTP, pero requiere pasos previos de seguridad:

**Requisitos en la cuenta Gmail del colegio:**

1. Activar **verificación en 2 pasos** en [myaccount.google.com/security](https://myaccount.google.com/security)
2. Generar un **App Password** en [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords):
    - Seleccionar app: "Correo"
    - Seleccionar dispositivo: "Otra (CienciasNET)"
    - Copiar el password de 16 caracteres generado
3. Usar ese password como valor de `MAIL_PASSWORD` en `.env`

**IMPORTANTE:** `MAIL_FROM_ADDRESS` debe ser igual a `MAIL_USERNAME`. Gmail no permite enviar correos desde una
dirección diferente a la cuenta autenticada.

**Prefijo en asuntos:** Todos los correos automáticos llevan el prefijo `[CienciasNET]` en el asunto para que los padres
los identifiquen fácilmente (ej: `[CienciasNET] Registro de ingreso — Juan Pérez`). Esto se configura en
`MAIL_SUBJECT_PREFIX`.

**Variables en `.env`:**

| Variable              | Ejemplo                      | Notas                         |
|-----------------------|------------------------------|-------------------------------|
| `MAIL_MAILER`         | `smtp`                       |                               |
| `MAIL_HOST`           | `smtp.gmail.com`             |                               |
| `MAIL_PORT`           | `587`                        |                               |
| `MAIL_USERNAME`       | `colegio.ciencias@gmail.com` | Cuenta Gmail del colegio      |
| `MAIL_PASSWORD`       | `aaaa_bbbb_cccc_dddd`        | App Password de 16 caracteres |
| `MAIL_ENCRYPTION`     | `tls`                        |                               |
| `MAIL_FROM_ADDRESS`   | `mismo que MAIL_USERNAME`    | Gmail no permite spoofing     |
| `MAIL_FROM_NAME`      | `Colegio Ciencias`           | Nombre visible en la bandeja  |
| `MAIL_SUBJECT_PREFIX` | `[CienciasNET] `             | Prefijo en todos los asuntos  |

---

## B.4. Build y Deploy

```bash
cd /var/www/CienciasNET

# Clonar el repositorio (primera vez)
git clone git@github.com:iovargasjeff/CienciasNET.git .
cp .env.docker.example .env && nano .env

# Construir y levantar todos los servicios
docker compose up -d --build

# Verificar que todo esté corriendo
docker compose ps
docker compose logs -f
```

El primer deploy tarda 2-3 minutos (build de imágenes + migraciones). Los siguientes son casi instantáneos.

---

## B.4.1. Configuración de Cloudflare R2

- Crear un bucket exclusivo para biometría y mantener desactivado el acceso público.
- Crear credenciales S3 limitadas únicamente a ese bucket.
- Guardar fotos de enrolamiento bajo `enrollment/{user_uuid}/`.
- Guardar evidencia excepcional bajo `evidence/YYYY/MM/DD/`.
- Configurar eliminación automática de evidencia según `expira_en`, con máximo recomendado de 30 días.
- No usar R2 para video ni para capturas rutinarias.
- Verificar periódicamente que los objetos eliminados o revocados no queden huérfanos.

El acceso de revisión se realiza mediante URLs firmadas de corta duración generadas por Laravel. Nunca se guarda una URL
firmada en PostgreSQL; solo se conserva `r2_object_key`.

---

## B.5. Configurar Nginx del Host

El Nginx del host actúa como reverse proxy hacia los contenedores:

`/etc/nginx/sites-available/cienciasnet`:

```nginx
# Frontend (contenedor cienciasnet-frontend)
server {
    listen 443 ssl http2;
    server_name cienciascolegio.pe;

    ssl_certificate /etc/letsencrypt/live/cienciascolegio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/cienciascolegio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

# API (contenedor cienciasnet-backend por PHP-FPM)
server {
    listen 443 ssl http2;
    server_name api.cienciascolegio.pe;

    ssl_certificate /etc/letsencrypt/live/api.cienciascolegio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.cienciascolegio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://127.0.0.1:8080/api/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}

server {
    listen 80;
    server_name cienciascolegio.pe api.cienciascolegio.pe;
    return 301 https://$host$request_uri;
}
```

```bash
ln -s /etc/nginx/sites-available/cienciasnet /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## B.6. SSL con Let's Encrypt

```bash
certbot --nginx -d cienciascolegio.pe -d api.cienciascolegio.pe
```

---

## B.7. CI/CD con Docker

`.github/workflows/deploy.yml`:

```yaml
name: Deploy CienciasNET (Docker)

on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd /var/www/CienciasNET
            git pull origin main
            docker compose up -d --build
            docker compose exec -T backend php artisan migrate --force
            docker compose exec -T backend php artisan config:cache
            docker compose exec -T backend php artisan route:cache
```

---

## B.8. Backups Integrales (Docker)

La configuración ejecutable de producción vive en [`../../docker-compose.production.yml`](../../docker-compose.production.yml)
y las tareas de operación en [`../../ops/production/README.md`](../../ops/production/README.md). Usar esos artefactos
como base para validar puertos privados, scheduler, worker, backup cifrado y restore aislado.

```bash
mkdir -p /backups/postgresql
crontab -e
```

```cron
# Backup diario a las 3:00 AM hora Lima (UTC-5 = 08:00 UTC)
0 8 * * * docker exec cienciasnet-db pg_dump -U cienciasnet_user cienciasnet | gzip > /backups/postgresql/cienciasnet_$(date +\%Y\%m\%d).sql.gz

# Este dump es solo una parte del backup integral. El job debe cifrarlo, copiar también
# storage/app/private y el inventario/objetos R2, y replicarlo fuera del VPS.
```

```bash
# Restaurar backup
gunzip -c /backups/postgresql/cienciasnet_20250101.sql.gz | docker exec -i cienciasnet-db psql -U cienciasnet_user cienciasnet
```

La restauración no se considera exitosa hasta verificar base de datos, archivos privados, objetos R2, lectura de datos
cifrados y acceso funcional desde una instalación aislada. Debe probarse trimestralmente.

---

## B.9. Comandos Útiles (Docker)

```bash
# Estado de los contenedores
docker compose ps

# Ver logs en tiempo real (todos)
docker compose logs -f

# Ver logs de un servicio específico
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f queue
docker compose logs -f facial-api

# Reiniciar un servicio
docker compose restart backend
docker compose restart facial-api

# Ejecutar comandos Artisan
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan tinker

# Conectar a PostgreSQL dentro del contenedor
docker compose exec db psql -U cienciasnet_user cienciasnet

# Ver espacio en disco de volúmenes
docker system df

# Actualizar a nueva versión (después de git pull)
docker compose up -d --build

# Detener todo
docker compose down

# Detener y eliminar volúmenes (⚠️ borra la BD)
docker compose down -v
```
