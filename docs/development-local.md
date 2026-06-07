# Desarrollo Local Reproducible

Docker Compose es el entorno oficial de desarrollo y evita depender de versiones particulares instaladas en cada PC.

## Requisitos

- Git.
- Docker Desktop reciente con Docker Compose v2.
- Al menos 8 GB de RAM disponibles para Docker.
- Puertos locales `5173` y `8000` libres.

PHP, Composer, PostgreSQL, Python y Node.js no son obligatorios en el host. Se ejecutan dentro de contenedores.

## Primer arranque

```bash
git clone https://github.com/iovargasjeff/ciencias-net
cd ciencias-net
docker compose up -d --build
docker compose exec backend php artisan migrate:fresh --seed
docker compose ps
```

| Servicio | Uso | Acceso host |
|---|---|---|
| `frontend` | SPA React/Vite | `http://localhost:5173` |
| `backend` | Laravel API | `http://localhost:8000` |
| `queue` | Worker Laravel | No expuesto |
| `db` | PostgreSQL 16 | No expuesto |
| `facial-api` | FastAPI facial privado | No expuesto |

Los valores predeterminados de Compose son exclusivos para desarrollo local. Para personalizarlos:

```bash
cp .env.docker.example .env
```

## Comandos diarios

```bash
docker compose up -d
docker compose ps
docker compose logs -f backend frontend
docker compose exec backend php artisan test
docker compose exec frontend npm run test
docker compose exec frontend npm run quality
docker compose down
```

`docker compose down` conserva PostgreSQL. `docker compose down -v` elimina volúmenes y borra la base local.
El frontend ejecuta `npm ci` al iniciar para sincronizar su volumen de dependencias con `package-lock.json`.

## Verificación completa

```bash
docker compose config --quiet
docker compose exec backend php vendor/bin/pint --test
docker compose exec backend php artisan test
docker compose exec backend php artisan scribe:generate
docker compose exec frontend npm run lint
docker compose exec frontend npm run test
docker compose exec frontend npm run build
```

Las pruebas E2E se ejecutan de forma reproducible con la imagen oficial de Playwright:

```bash
docker compose --profile test run --rm frontend-e2e
```

Comprobar además que `/health`, `/api/v1/health` y `http://localhost:5173` respondan, y que `docker compose ps` muestre
todos los healthchecks saludables.

## Diagnóstico

```bash
docker compose logs --tail=100 backend
docker compose logs --tail=100 frontend
docker compose logs --tail=100 facial-api
docker compose exec db pg_isready -U cienciasnet -d cienciasnet
docker compose build --no-cache <servicio>
```

No instalar paquetes manualmente dentro de un contenedor en ejecución como solución permanente; actualizar el manifiesto
y lockfile correspondiente.

## Flujo de Trabajo en GitHub (Feature Branches)

Para mantener el código ordenado y profesional, todo el equipo y los agentes de IA deben seguir el patrón de "Ramas por Funcionalidad" (Feature Branches):

1. **Ramas por Change (Feature):**
   Nunca se trabaja directamente en la rama `main`. Cada vez que inicies un change (ej. `BE-004`), creas una nueva rama a partir de `main` con un nombre descriptivo:
   ```bash
   git checkout main
   git pull origin main
   git checkout -b feature/BE-004-add-roles
   ```

2. **Pull Requests (PR):**
   Al terminar la funcionalidad, subes tu rama a GitHub y abres un **Pull Request**. No uses Issues para esto, el EXECUTION_PLAN.md ya organiza las tareas. El PR sirve para que el "Reviewer" (Jefferson o André) lea el código y apruebe los cambios.

3. **Merge y Eliminación:**
   Una vez aprobado, fusionas (Merge) el PR hacia `main`. ¡Inmediatamente después, **eliminas la rama** `feature/BE-004-add-roles`! Las ramas nacen para una tarea específica y mueren al terminarla.

4. **Sincronización:**
   Tus compañeros (que estaban haciendo otras tareas) solo deben volver a `main` y descargar lo nuevo para estar al día:
   ```bash
   git checkout main
   git pull origin main
   docker compose up -d --build
   ```
