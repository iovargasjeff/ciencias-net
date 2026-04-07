# Arquitectura — CienciasNET

## Stack: Laravel API + Next.js SPA

Arquitectura desacoplada. Laravel expone una API REST consumida por Next.js. La misma API puede servir en el futuro a una app móvil.

---

## Diagrama General

```
┌──────────────────────────────────────────────────────────────┐
│                      VPS Hetzner CX32                        │
│                  Ubuntu 22.04 LTS                            │
│                                                              │
│  ┌──────────────────┐   HTTPS   ┌────────────────────────┐  │
│  │   Next.js 14      │ ◄───────► │   Laravel 11 API        │  │
│  │   PM2 :3000       │           │   PHP-FPM 8.2          │  │
│  └──────────────────┘           └───────────┬────────────┘  │
│                                              │               │
│                                 ┌────────────▼────────────┐  │
│                                 │     PostgreSQL 16        │  │
│                                 │     Puerto 5432          │  │
│                                 │   (solo localhost)       │  │
│                                 └─────────────────────────┘  │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  /var/www/CienciasNET/backend/storage/app/public/   │    │
│  │  ├── fotos/       ← Fotos de alumnos (WebP)         │    │
│  │  ├── separatas/   ← PDFs de materiales              │    │
│  │  ├── comprobantes/← Comprobantes de pago            │    │
│  │  └── temp/        ← Archivos temporales             │    │
│  │                                                     │    │
│  │  Servidos por Nginx en /storage/ con cache headers  │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Nginx — Reverse Proxy + SSL (Let's Encrypt)         │   │
│  │  ciencias.dominio.pe      → Next.js :3000            │   │
│  │  api.ciencias.dominio.pe  → PHP-FPM (Laravel)        │   │
│  └──────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────┘
```

---

## Almacenamiento e Imágenes en el VPS

### ¿Por qué almacenar en el VPS?

Para una academia con pocos cientos de alumnos, el almacenamiento local en el VPS es la opción más económica y sencilla:

| Criterio | VPS Local ✅ | Cloudflare R2 |
|---|---|---|
| Costo | Incluido en el VPS | $0.015/GB/mes (después del free tier) |
| Configuración | Cero configuración extra | Cuenta externa, credenciales API |
| Latencia | Mínima (mismo servidor) | Depende de la red |
| Ideal para | Hasta ~5 GB de archivos | Volúmenes grandes o multi-servidor |
| Backup | Con el backup del VPS | Independiente del VPS |

Con 80 GB de SSD en el Hetzner CX32 y considerando que la base de datos PostgreSQL ocupa ~500 MB–2 GB, quedan más de 70 GB disponibles para archivos — suficiente para años de operación de una academia.

### Intervention Image v3 — Optimización de imágenes en PHP

Cada imagen subida (foto de alumno, comprobante escaneado) es procesada automáticamente antes de guardarse:

```php
// Instalación
// composer require intervention/image

// app/Modules/Alumnos/Application/UseCases/SubirFotoAlumno.php

use Intervention\Image\Laravel\Facades\Image;

public function handle(UploadedFile $file, string $alumnoId): string
{
    $imagen = Image::read($file);

    // 1. Redimensionar manteniendo proporción (máximo 800x800px)
    $imagen->scaleDown(width: 800, height: 800);

    // 2. Convertir a WebP (ocupa ~30% menos que JPEG)
    $nombre = $alumnoId . '_' . time() . '.webp';
    $ruta   = 'fotos/' . $nombre;

    // 3. Guardar con calidad 80% (balance calidad/tamaño)
    $imagen->toWebp(quality: 80)
           ->save(storage_path('app/public/' . $ruta));

    return $ruta; // Se guarda esta ruta en PostgreSQL
}
```

### Tipos de archivo y política por carpeta

| Carpeta | Contenido | Optimización | Acceso |
|---|---|---|---|
| `storage/app/public/fotos/` | Fotos de alumnos | WebP, max 800px, calidad 80% | Autenticado |
| `storage/app/public/separatas/` | PDFs de materiales | Sin modificar (PDF) | Autenticado |
| `storage/app/public/comprobantes/` | Comprobantes de pago | Sin modificar (PDF/imagen) | Solo admin |
| `storage/app/public/temp/` | Archivos temporales | — | Eliminados tras 24h |

### Servir archivos con Nginx (con autenticación vía Laravel)

Los archivos **no son públicos directamente**. Se accede a ellos a través de un endpoint de Laravel que valida el token antes de servir el archivo:

```php
// routes/api.php
Route::middleware('auth:sanctum')->get('/archivos/{ruta}', function (string $ruta) {
    $path = storage_path('app/public/' . $ruta);

    abort_if(!file_exists($path), 404);

    // Aquí también va la validación de permisos por rol/Policy

    return response()->file($path, [
        'Cache-Control' => 'private, max-age=3600',
    ]);
});
```

### Configuración de Nginx para archivos estáticos (si fueran públicos)

```nginx
# Solo si decides hacer alguna carpeta pública (ej: logos)
location /storage/publico/ {
    alias /var/www/CienciasNET/backend/storage/app/public/publico/;
    expires 30d;
    add_header Cache-Control "public, immutable";
    add_header Vary Accept-Encoding;
}
```

---

## Clean Architecture + Vertical Slice

Cada módulo es un **slice vertical independiente** con sus propias capas internas.

### Estructura de un módulo (ejemplo: Notas)

```
app/Modules/Notas/
├── Application/
│   ├── UseCases/
│   │   ├── RegistrarNota.php
│   │   ├── ActualizarNota.php
│   │   ├── ObtenerNotasAlumno.php
│   │   └── GenerarBoletaPDF.php
│   └── DTOs/
│       └── NotaDTO.php
├── Domain/
│   ├── Entities/
│   │   └── Nota.php
│   ├── ValueObjects/
│   │   └── TipoEvaluacion.php       # Enum: fast_test | semanal | simulacro
│   └── Repositories/
│       └── NotaRepositoryInterface.php
├── Infrastructure/
│   ├── Models/
│   │   └── NotaModel.php            # Eloquent → tabla notas en PostgreSQL
│   └── Repositories/
│       └── EloquentNotaRepository.php
└── Presentation/
    ├── Controllers/
    │   └── NotaController.php
    ├── Requests/
    │   └── RegistrarNotaRequest.php
    └── Resources/
        └── NotaResource.php
```

### Regla de dependencias

```
Presentation  ──►  Application  ──►  Domain
Infrastructure  ──────────────────►  Domain (implementa interfaces)

✅ Domain: no depende de nada externo
✅ Application: solo conoce Domain
✅ Infrastructure: implementa contratos del Domain
✅ Presentation: solo invoca Use Cases de Application
❌ Domain: NUNCA importa de Infrastructure ni Presentation
```

---

## Organización Completa del Repositorio

```
CienciasNET/
│
├── backend/                              ← Laravel 11 API
│   ├── app/
│   │   ├── Modules/
│   │   │   ├── Auth/
│   │   │   │   ├── Application/UseCases/
│   │   │   │   ├── Domain/
│   │   │   │   ├── Infrastructure/Models/
│   │   │   │   └── Presentation/Controllers/
│   │   │   ├── Alumnos/
│   │   │   ├── Padres/
│   │   │   ├── Docentes/
│   │   │   ├── Notas/
│   │   │   ├── Asistencia/
│   │   │   ├── Pagos/
│   │   │   ├── Comunicados/
│   │   │   ├── Materiales/
│   │   │   ├── Horarios/
│   │   │   └── Reportes/
│   │   └── Shared/
│   │       ├── Exceptions/
│   │       ├── Traits/
│   │       │   └── AuditableTrait.php
│   │       └── BaseRepository.php
│   ├── database/
│   │   ├── migrations/                  ← TODAS las migraciones PostgreSQL
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   │   ├── 2025_01_01_000001_create_alumno_perfiles_table.php
│   │   │   ├── 2025_01_01_000002_create_padre_perfiles_table.php
│   │   │   ├── 2025_01_01_000003_create_docente_perfiles_table.php
│   │   │   ├── 2025_01_01_000004_create_alumno_padre_table.php
│   │   │   ├── 2025_01_01_000005_create_grupos_table.php
│   │   │   ├── 2025_01_01_000006_create_cursos_table.php
│   │   │   ├── 2025_01_01_000007_create_matriculas_table.php
│   │   │   ├── 2025_01_01_000008_create_sesiones_table.php
│   │   │   ├── 2025_01_01_000009_create_evaluaciones_table.php
│   │   │   ├── 2025_01_01_000010_create_notas_table.php
│   │   │   ├── 2025_01_01_000011_create_asistencias_table.php
│   │   │   ├── 2025_01_01_000012_create_pagos_table.php
│   │   │   ├── 2025_01_01_000013_create_materiales_table.php
│   │   │   ├── 2025_01_01_000014_create_comunicados_table.php
│   │   │   ├── 2025_01_01_000015_create_horarios_table.php
│   │   │   └── 2025_01_01_000016_create_audit_logs_table.php
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       ├── RolesAndPermissionsSeeder.php
│   │       └── AdminUserSeeder.php
│   ├── routes/
│   │   └── api.php
│   ├── storage/
│   │   └── app/
│   │       └── public/
│   │           ├── fotos/           ← Fotos de alumnos (WebP optimizadas)
│   │           ├── separatas/       ← PDFs de materiales
│   │           ├── comprobantes/    ← Comprobantes de pago
│   │           └── temp/            ← Temporales (limpieza automática)
│   ├── config/
│   ├── tests/
│   ├── .env.example
│   └── composer.json
│
├── frontend/                             ← Next.js 14
│   ├── app/
│   │   ├── (auth)/login/page.tsx
│   │   └── (dashboard)/
│   │       ├── layout.tsx
│   │       ├── alumno/
│   │       │   ├── notas/page.tsx
│   │       │   ├── asistencia/page.tsx
│   │       │   └── material/page.tsx
│   │       ├── padre/
│   │       │   ├── dashboard/page.tsx
│   │       │   ├── notas/page.tsx
│   │       │   └── pagos/page.tsx
│   │       ├── docente/
│   │       │   ├── mis-grupos/page.tsx
│   │       │   └── registrar-notas/page.tsx
│   │       ├── coordinador/
│   │       │   └── reportes/page.tsx
│   │       └── admin/
│   │           ├── alumnos/page.tsx
│   │           ├── pagos/page.tsx
│   │           └── usuarios/page.tsx
│   ├── components/
│   │   ├── ui/                      ← shadcn/ui
│   │   ├── shared/                  ← Sidebar, Header
│   │   └── modules/                 ← Componentes por módulo
│   ├── hooks/
│   ├── lib/
│   │   ├── api.ts
│   │   └── auth.ts
│   ├── stores/
│   ├── types/
│   ├── .env.example
│   └── package.json
│
├── docs/
│   ├── README.md
│   ├── architecture.md
│   ├── database.md
│   ├── modules.md
│   ├── security.md
│   ├── deployment.md
│   ├── business-model.md
│   └── reunion-funcionalidades.md
│
└── .github/
    └── workflows/
        └── deploy.yml
```

---

## Convención de Migraciones

Las migraciones viven en `backend/database/migrations/`. Se ejecutan en orden por timestamp. Cada tabla tiene su propia migración.

```bash
# Crear nueva migración
php artisan make:migration create_notas_table

# Ejecutar migraciones pendientes (después de git pull)
php artisan migrate

# Ver estado de todas las migraciones
php artisan migrate:status

# Revertir última migración (solo dev)
php artisan migrate:rollback

# Reset completo con seeders (solo dev, NUNCA en producción)
php artisan migrate:fresh --seed
```

**Regla del equipo:** Cada vez que un miembro crea una migración y hace push, los demás deben ejecutar `php artisan migrate` al hacer `git pull`.

---

## Flujo de Autenticación

```
1. POST /api/auth/login  { email, password }
2. Laravel valida en PostgreSQL (tabla users)
3. Retorna: { token, user: { id, nombre, rol, permisos } }
4. Next.js guarda token en memoria + cookie httpOnly
5. Cada request: Authorization: Bearer {token}
6. Laravel Sanctum verifica token en personal_access_tokens (PostgreSQL)
7. Policy verifica acceso al recurso específico
8. Token expirado → 401 → Next.js redirige a /login
```

---

## Matriz de Roles y Acceso

| Módulo | Alumno | Padre | Docente | Coordinador | Admin | Director |
|---|---|---|---|---|---|---|
| Ver mis notas | ✅ | ✅ hijo | ❌ | ✅ | ✅ | ✅ |
| Registrar notas | ❌ | ❌ | ✅ su grupo | ✅ | ✅ | ✅ |
| Ver asistencia | ✅ propia | ✅ hijo | ✅ su grupo | ✅ | ✅ | ✅ |
| Registrar asistencia | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ |
| Ver pagos | ❌ | ✅ hijo | ❌ | ❌ | ✅ | ✅ |
| Registrar pagos | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| Subir material | ❌ | ❌ | ✅ su curso | ✅ | ✅ | ✅ |
| Ver material | ✅ | ❌ | ✅ | ✅ | ✅ | ✅ |
| Publicar comunicados | ❌ | 👁️ | 👁️ | ✅ | ✅ | ✅ |
| Gestión de usuarios | ❌ | ❌ | ❌ | ❌ | ✅ | ✅ |
| Reportes globales | ❌ | ❌ | ❌ | ✅ | ✅ | ✅ |
