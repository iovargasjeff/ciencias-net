# Guía de Despliegue — CienciasNET (VPS Hetzner)

## Servidor Recomendado

| Plan | vCPU | RAM | Disco | Precio/mes |
|---|---|---|---|---|
| **Hetzner CX32** | 4 | 8 GB | 80 GB SSD | ~$10 |

Ubuntu 22.04 LTS. Suficiente para Laravel + Next.js + PostgreSQL para hasta 500 alumnos.

---

## 1. Preparación

```bash
ssh root@IP_DEL_VPS
apt update && apt upgrade -y
adduser cienciasnet
usermod -aG sudo cienciasnet
```

---

## 2. Instalar Nginx

```bash
apt install nginx -y
systemctl enable nginx && systemctl start nginx
```

---

## 3. Instalar PHP 8.2 + Intervention Image + extensiones

```bash
add-apt-repository ppa:ondrej/php -y && apt update
apt install php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring \
  php8.2-xml php8.2-curl php8.2-zip php8.2-intl php8.2-bcmath \
  php8.2-gd php8.2-imagick -y

# php8.2-gd y php8.2-imagick son necesarios para Intervention Image v3
php -v
```

Después de instalar Laravel, agregar Intervention Image:
```bash
composer require intervention/image
# Publicar configuración
php artisan vendor:publish --provider="Intervention\Image\Laravel\Providers\ImageServiceProvider"
```

---

## 4. Instalar PostgreSQL 16

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

PostgreSQL solo escucha en `127.0.0.1` por defecto — seguro sin configuración adicional.

---

## 5. Instalar Composer y Node.js

```bash
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install nodejs -y
npm install -g pm2
```

---

## 6. Configurar Nginx

`/etc/nginx/sites-available/cienciasnet`:

```nginx
# Backend Laravel API
server {
    listen 443 ssl http2;
    server_name api.ciencias.dominio.pe;

    root /var/www/CienciasNET/backend/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/api.ciencias.dominio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.ciencias.dominio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # IMPORTANTE: bloquear acceso directo a /storage/
    location ^~ /storage/ {
        deny all;
    }

    location ~ /\.ht { deny all; }
}

# Frontend Next.js
server {
    listen 443 ssl http2;
    server_name ciencias.dominio.pe;

    ssl_certificate /etc/letsencrypt/live/ciencias.dominio.pe/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/ciencias.dominio.pe/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_cache_bypass $http_upgrade;
    }
}

server {
    listen 80;
    server_name ciencias.dominio.pe api.ciencias.dominio.pe;
    return 301 https://$host$request_uri;
}
```

```bash
ln -s /etc/nginx/sites-available/cienciasnet /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

---

## 7. SSL con Let's Encrypt

```bash
apt install certbot python3-certbot-nginx -y
certbot --nginx -d ciencias.dominio.pe -d api.ciencias.dominio.pe
```

---

## 8. Deploy del Backend Laravel

```bash
cd /var/www/CienciasNET/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
nano .env   # Configurar DB_CONNECTION=pgsql, DB_HOST, DB_PORT, DB_DATABASE, etc.

php artisan migrate --force
php artisan db:seed --force

# Crear enlace simbólico para almacenamiento
php artisan storage:link

# Crear carpetas de almacenamiento
mkdir -p storage/app/public/fotos
mkdir -p storage/app/public/separatas
mkdir -p storage/app/public/comprobantes
mkdir -p storage/app/public/temp

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permisos correctos
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 9. Deploy del Frontend Next.js

```bash
cd /var/www/CienciasNET/frontend
npm install
cp .env.example .env.local
nano .env.local  # NEXT_PUBLIC_API_URL=https://api.ciencias.dominio.pe
npm run build
pm2 start npm --name "cienciasnet-frontend" -- start
pm2 save && pm2 startup
```

---

## 10. CI/CD con GitHub Actions

`.github/workflows/deploy.yml`:

```yaml
name: Deploy CienciasNET

on:
  push:
    branches: [main]

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
            pm2 restart cienciasnet-frontend
```

**Secrets requeridos en GitHub → Settings → Secrets:**
- `VPS_HOST` — IP del VPS
- `VPS_USER` — `cienciasnet`
- `VPS_SSH_KEY` — Clave SSH privada

---

## 11. Backups Automáticos de PostgreSQL

```bash
mkdir -p /backups/postgresql
crontab -e

# Backup diario a las 3:00 AM hora Lima (UTC-5 = 08:00 UTC)
0 8 * * * PGPASSWORD='contraseña' pg_dump -U cienciasnet_user -h 127.0.0.1 cienciasnet | gzip > /backups/postgresql/cienciasnet_$(date +\%Y\%m\%d).sql.gz

# Eliminar backups de más de 30 días
30 8 * * * find /backups/postgresql/ -name "*.sql.gz" -mtime +30 -delete
```

---

## 12. Limpieza de Archivos Temporales

```bash
# Agregar al crontab: limpiar /storage/app/public/temp/ cada día
0 9 * * * find /var/www/CienciasNET/backend/storage/app/public/temp/ -mtime +1 -delete
```

---

## Comandos Útiles

```bash
# Logs de Laravel en tiempo real
tail -f /var/www/CienciasNET/backend/storage/logs/laravel.log

# Estado del frontend (Next.js)
pm2 status
pm2 logs cienciasnet-frontend

# Reiniciar PHP-FPM
systemctl restart php8.2-fpm

# Conectar a PostgreSQL
sudo -u postgres psql cienciasnet

# Ver espacio en disco (importante para archivos)
df -h /var/www/CienciasNET/backend/storage/
du -sh /var/www/CienciasNET/backend/storage/app/public/*/
```
