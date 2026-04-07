# CienciasNET — Plataforma Intranet para Academia CIENCIAS

> Portal académico y administrativo integral para Academia CIENCIAS, orientado a la gestión de alumnos menores de edad, padres de familia, docentes y personal administrativo.

---

## Descripción del Proyecto

**CienciasNET** es una plataforma web intranet diseñada para Academia CIENCIAS. Centraliza en un solo portal el seguimiento académico de alumnos, la comunicación con padres de familia, la administración de pagos y el trabajo diario de los docentes — con seguridad, trazabilidad y experiencia de usuario como pilares principales.

Al tratarse de una institución que atiende mayormente **menores de edad**, el sistema otorga a los padres o apoderados acceso completo al desempeño académico de sus hijos: notas, asistencia, alertas y comunicados.

---

## Modelo de Entrega

| Concepto | Detalle |
|---|---|
| **Pago único** | Desarrollo e implementación completa del sistema |
| **Mantenimiento mensual** | Hosting, SSL, dominio, soporte técnico, corrección de bugs y actualizaciones de seguridad |

El cliente recibe el sistema funcionando en producción. El equipo CienciasNET gestiona la infraestructura dentro del mantenimiento mensual.

---

## Stack Tecnológico

### Backend — Laravel 11 (API)

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP 8.2+ |
| Framework | Laravel 11 (API Mode) |
| Arquitectura | Clean Architecture + Vertical Slice |
| Autenticación | Laravel Sanctum |
| Base de datos | **PostgreSQL 16** |
| ORM | Eloquent ORM + Query Builder |
| Migraciones | Laravel Migrations |
| Roles y permisos | Spatie Laravel Permission |
| Correos | Laravel Mail + SMTP |
| Almacenamiento e imágenes | **VPS local** — Laravel Storage (disco local) + Intervention Image (optimización) |
| Testing | Pest PHP |
| Documentación API | Laravel Scribe |

### Frontend — Next.js 14

| Componente | Tecnología |
|---|---|
| Framework | Next.js 14 (App Router) |
| Lenguaje | TypeScript |
| Estilos | Tailwind CSS v4 |
| Componentes UI | shadcn/ui |
| Estado global | Zustand |
| Peticiones HTTP | Axios + TanStack Query |
| Formularios | React Hook Form + Zod |
| Gráficas | Recharts |
| Iconos | Lucide React |

### Infraestructura — VPS Hetzner

| Componente | Tecnología |
|---|---|
| Servidor | Hetzner CX32 — 4 vCPU, 8 GB RAM, 80 GB SSD (~$10/mes) |
| OS | Ubuntu 22.04 LTS |
| Web server | Nginx |
| PHP runtime | PHP-FPM 8.2 |
| Base de datos | **PostgreSQL 16** (instalación directa, sin Docker) |
| Almacenamiento archivos | **Disco local del VPS** (`/storage/app/public/`) |
| Optimización de imágenes | **Intervention Image v3** (PHP) — convierte a WebP y redimensiona |
| Proceso frontend | PM2 |
| Colas Laravel | Supervisor |
| SSL | Let's Encrypt (renovación automática) |
| CI/CD | GitHub Actions → SSH deploy |

---

## Módulos del Sistema

| # | Módulo | Descripción |
|---|---|---|
| 1 | Auth | Login, roles, recuperación de contraseña |
| 2 | Alumnos | Fichas, matrícula, vinculación con padres |
| 3 | Notas | Fast Test, evaluaciones semanales, simulacros |
| 4 | Asistencia | Registro por sesión, justificaciones, alertas |
| 5 | Portal del Padre | Dashboard de seguimiento del hijo |
| 6 | Pagos | Estado de cuenta, recibos PDF, morosidad |
| 7 | Materiales | Separatas, videos y recursos por curso |
| 8 | Comunicados | Avisos oficiales con notificación por correo |
| 9 | Horarios | Horarios por grupo y calendario académico |
| 10 | Docentes | Panel de registro de notas y asistencia |
| 11 | Reportes | Estadísticas, rankings, exportación Excel/PDF |

---

## Roles del Sistema

| Rol | Descripción |
|---|---|
| `alumno` | Estudiante matriculado |
| `padre` | Padre o apoderado vinculado al alumno |
| `docente` | Profesor asignado a un curso y grupo |
| `coordinador` | Supervisión académica y reportes |
| `administrador` | Matrículas, pagos y gestión de usuarios |
| `director` | Acceso total y estadísticas globales |

---

## Estado del Proyecto

| Fase | Estado |
|---|---|
| Documentación y arquitectura | ✅ En progreso |
| Setup Laravel + Next.js | ⬜ Pendiente |
| Migraciones PostgreSQL | ⬜ Pendiente |
| Módulo Auth | ⬜ Pendiente |
| Módulo Alumnos | ⬜ Pendiente |
| Módulo Notas | ⬜ Pendiente |
| Módulo Asistencia | ⬜ Pendiente |
| Portal del Padre | ⬜ Pendiente |
| Módulo Pagos | ⬜ Pendiente |
| Módulo Docentes | ⬜ Pendiente |
| Reportes | ⬜ Pendiente |
| Deploy en VPS Hetzner | ⬜ Pendiente |

---

## Repositorio

**GitHub:** `iovargasjeff/CienciasNET`

```
CienciasNET/
├── backend/      ← Laravel 11 API
├── frontend/     ← Next.js 14
├── docs/         ← Documentación del proyecto
└── .github/
    └── workflows/
        └── deploy.yml
```
