# Arquitectura — CienciasNET (Colegio Ciencias)

## Stack: Laravel 11 API + React 18 / Vite SPA

Arquitectura desacoplada tipo Monorepo. Laravel expone una API REST consumida por React (SPA con Vite). La misma API
puede servir en el futuro a una app móvil.

---

## Diagrama General

```
┌──────────────────────────────────────────────────────────────┐
│                      VPS Hetzner CX32                        │
│                  Ubuntu 22.04 LTS                            │
│                                                              │
│  ┌──────────────────┐   HTTPS    ┌───────────────────────-┐  │
│  │   Nginx (static) │ ◄───────►  │   Laravel 11 API       │  │
│  │   React build    │            │   PHP-FPM 8.2          │  │
│  │   /dist/         │            └───────────┬────────────┘  │
│  └──────────────────┘                        │               │
│                                 ┌────────────▼────────────┐  │
│                                 │     PostgreSQL 16       │  │
│                                 │     Puerto 5432         │  │
│                                 │   (solo localhost)      │  │
│                                 └─────────────────────────┘  │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  Nginx — Reverse Proxy + SSL (Let's Encrypt)         │    │
│  │  cienciascolegio.pe        → React build (static)    │    │
│  │  api.cienciascolegio.pe    → PHP-FPM (Laravel)       │    │ 
│  └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

---

## Clean Architecture + Vertical Slice

Cada módulo es un **slice vertical independiente** con sus propias capas internas.

### Estructura de un módulo (ejemplo: Academico)

```
app/Modules/Academico/
├── Application/
│   ├── UseCases/
│   │   ├── CrearExamen.php
│   │   ├── RegistrarNota.php
│   │   ├── PublicarNotas.php
│   │   └── ObtenerRanking.php
│   └── DTOs/
│       └── NotaDTO.php
├── Domain/
│   ├── Entities/
│   │   └── Examen.php
│   ├── ValueObjects/
│   │   └── CanalExamen.php        # Enum: general | ciencias | letras
│   └── Repositories/
│       └── ExamenRepositoryInterface.php
├── Infrastructure/
│   ├── Models/
│   │   ├── ExamenModel.php
│   │   └── NotaModel.php
│   └── Repositories/
│       └── EloquentExamenRepository.php
└── Presentation/
    ├── Controllers/
    │   └── ExamenController.php
    ├── Requests/
    │   └── RegistrarNotaRequest.php
    └── Resources/
        └── ExamenResource.php
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
│   │   │   ├── Auth/                     # Login, roles, recuperación
│   │   │   ├── Asistencia/               # Asistencias alumnos + docentes
│   │   │   ├── Finanzas/                 # Pagos, conceptos, descuentos
│   │   │   ├── Academico/                # Exámenes, notas, rankings
│   │   │   ├── TOE/                      # Incidencias, derivaciones
│   │   │   ├── Usuarios/                 # CRUD de usuarios y perfiles
│   │   │   ├── Materiales/               # Recursos por curso
│   │   │   ├── Horarios/                 # Horarios y calendario
│   │   │   └── Comunicados/              # Avisos institucionales
│   │   └── Shared/
│   │       ├── Exceptions/
│   │       ├── Traits/
│   │       │   └── AuditableTrait.php
│   │       └── BaseRepository.php
│   ├── database/
│   │   ├── migrations/
│   │   │   ├── 0001_01_01_000000_create_users_table.php
│   │   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   │   ├── 2025_01_01_000001_create_alumnos_table.php
│   │   │   ├── 2025_01_01_000002_create_padres_table.php
│   │   │   ├── 2025_01_01_000003_create_alumno_padre_table.php
│   │   │   ├── 2025_01_01_000004_create_docentes_table.php
│   │   │   ├── 2025_01_01_000005_create_administrativos_table.php
│   │   │   ├── 2025_01_01_000006_create_asistencias_alumnos_table.php
│   │   │   ├── 2025_01_01_000007_create_asistencias_docentes_table.php
│   │   │   ├── 2025_01_01_000008_create_conceptos_pago_table.php
│   │   │   ├── 2025_01_01_000009_create_pagos_table.php
│   │   │   ├── 2025_01_01_000010_create_incidencias_table.php
│   │   │   ├── 2025_01_01_000011_create_atenciones_psicologia_table.php
│   │   │   ├── 2025_01_01_000012_create_examenes_table.php
│   │   │   ├── 2025_01_01_000013_create_notas_table.php
│   │   │   ├── 2025_01_01_000014_create_materiales_table.php
│   │   │   ├── 2025_01_01_000015_create_horarios_table.php
│   │   │   ├── 2025_01_01_000016_create_comunicados_table.php
│   │   │   └── 2025_01_01_000017_create_audit_logs_table.php
│   │   └── seeders/
│   │       ├── DatabaseSeeder.php
│   │       ├── RolesAndPermissionsSeeder.php
│   │       └── AdminUserSeeder.php
│   ├── routes/
│   │   └── api.php
│   ├── config/
│   │   └── cors.php
│   ├── tests/
│   ├── .env.example
│   └── composer.json
│
├── frontend/                             ← React 18 + Vite SPA
│   ├── public/
│   │   └── favicon.ico
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── ui/                       ← shadcn/ui
│   │   │   └── shared/                   ← Sidebar, Header, ProtectedRoute
│   │   ├── features/
│   │   │   ├── auth/                     # Login, recuperar contraseña
│   │   │   ├── asistencia/               # Panel Auxiliar, registro ingreso/salida
│   │   │   ├── finanzas/                 # Panel Yanina, portal de pagos Padre
│   │   │   ├── academico/                # Exámenes, notas, rankings
│   │   │   ├── toe/                      # Incidencias, derivaciones a Psicología
│   │   │   ├── materiales/               # Recursos por curso
│   │   │   ├── horarios/                 # Horarios y calendario
│   │   │   └── comunicados/              # Avisos institucionales
│   │   ├── hooks/
│   │   │   ├── useAuth.ts
│   │   │   └── usePermissions.ts
│   │   ├── lib/
│   │   │   └── api.ts                    # Axios con interceptors
│   │   ├── routes/
│   │   │   └── index.tsx                 # React Router
│   │   ├── store/
│   │   │   └── authStore.ts              # Zustand
│   │   ├── types/
│   │   │   └── index.ts                  # DTOs del backend
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── index.html
│   ├── package.json
│   ├── tailwind.config.js
│   ├── tsconfig.json
│   └── vite.config.ts
│
├── docs/
│   ├── architecture.md
│   ├── business-model.md
│   ├── database.md
│   ├── deployment.md
│   ├── modules.md
│   ├── reunion-funcionalidades.md
│   └── security.md
│
└── .github/
    └── workflows/
        └── deploy.yml
```

---

## Convención de Migraciones

Las migraciones viven en `backend/database/migrations/`. Se ejecutan en orden por timestamp. Cada tabla tiene su propia
migración. Todas las PKs son UUID.

```bash
# Crear nueva migración
php artisan make:migration create_notas_table

# Ejecutar migraciones pendientes
php artisan migrate

# Ver estado
php artisan migrate:status
```

**Regla del equipo:** Cada vez que un miembro crea una migración y hace push, los demás deben ejecutar
`php artisan migrate` al hacer `git pull`.

---

## Flujo de Autenticación

```
1. POST /api/auth/login  { email, password }
2. Laravel valida en PostgreSQL (tabla users)
3. Retorna: { token, user: { id, nombre, rol, permisos } }
4. React guarda token en memoria + cookie httpOnly
5. Cada request: Authorization: Bearer {token}
6. Laravel Sanctum verifica token en personal_access_tokens (PostgreSQL)
7. Policy verifica acceso al recurso específico
8. Token expirado → 401 → React redirige a /login
```

---

## Matriz de Roles y Acceso

| Módulo                  | superadmin | toe | psicologia | auxiliar | coord_acad | administrativo | docente      | padre  |
|-------------------------|------------|-----|------------|----------|------------|----------------|--------------|--------|
| Gestión de usuarios     | ✅          | ❌   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌      |
| Asistencia alumnos      | ✅          | 👁️ | ❌          | ✅        | 👁️        | ❌              | ❌            | ✅ hijo |
| Justificar faltas       | ✅          | ✅   | ❌          | ✅        | ❌          | ❌              | ❌            | ❌      |
| Asistencia docentes     | ✅          | ❌   | ❌          | ❌        | ❌          | ✅              | ❌            | ❌      |
| Finanzas (pagos)        | ✅          | ❌   | ❌          | ❌        | ❌          | ✅              | ❌            | ✅ hijo |
| Exámenes (crear)        | ✅          | ❌   | ❌          | ❌        | ✅          | ❌              | ❌            | ❌      |
| Notas (registrar)       | ✅          | ❌   | ❌          | ❌        | ✅          | ❌              | 👁️ opcional | ❌      |
| Ver notas               | ✅          | 👁️ | 👁️        | 👁️      | ✅          | ❌              | ❌            | ✅ hijo |
| Incidencias (registrar) | ✅          | ✅   | ❌          | ✅        | ❌          | ❌              | ❌            | ❌      |
| Derivar a Psicología    | ✅          | ✅   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌      |
| Atenciones psicología   | ✅          | ❌   | ✅          | ❌        | ❌          | ❌              | ❌            | ❌      |
| Materiales              | ✅          | ❌   | ❌          | ❌        | ✅          | ❌              | ✅            | ✅ ver  |
| Horarios                | ✅          | 👁️ | 👁️        | 👁️      | ✅          | ❌              | ✅ ver        | ✅ ver  |
| Comunicados             | ✅          | ✅   | ✅          | 👁️      | ✅          | 👁️            | 👁️          | ✅      |
| Reportes globales       | ✅          | ❌   | ❌          | ❌        | ✅          | ✅              | ❌            | ❌      |

> 👁️ = Solo lectura / consulta. ✅ = Lectura y escritura.

---

## Comunicación CORS y API

En desarrollo:

- Frontend: `localhost:5173` (Vite dev server)
- Backend: `localhost:8000` (Laravel built-in server)

En producción:

- Nginx sirve los estáticos del build (`frontend/dist/`) directamente
- Nginx actúa como reverse proxy a PHP-FPM para `/api/*`

```php
// backend/config/cors.php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
'supports_credentials' => true,
```

```typescript
// frontend/src/lib/api.ts — Axios con interceptors
import axios from 'axios';

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
    headers: {'Accept': 'application/json'},
});
```
