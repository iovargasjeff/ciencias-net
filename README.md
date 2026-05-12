# CienciasNET — Sistema Intranet Administrativo y Académico

> Plataforma integral para el Colegio Ciencias. Centraliza la gestión académica, administrativa, financiera y disciplinaria en un solo sistema con notificaciones por correo electrónico.

**Institución:** Colegio Ciencias
**Stack:** Laravel 11 + React 18 / Vite (Arquitectura Monorepo) + PostgreSQL 16
**Notificaciones:** Exclusivamente por Correo Electrónico

---

## Jerarquía y Roles de Usuario

| Rol en Sistema | Actor Físico | Funciones Principales |
|---|---|---|
| `superadmin` | Promotor / Directora | Acceso total e irrestricto a todos los módulos, reportes y configuraciones |
| `toe` | Dpto. TOE | Coordinación de docentes, recepción de reportes, escalar a Psicología, notificación a padres |
| `psicologia` | Psicóloga(o) | Acceso a casos derivados, registro de atenciones confidenciales, soporte emocional |
| `auxiliar` | Auxiliar de Educación | Control de puerta (7:45 AM), registro de asistencias/tardanzas, Cuaderno de Incidencias |
| `coordinador_academico` | Coord. Académico | Malla curricular, exámenes semanales (Viernes a Martes), notas y rankings |
| `administrativo` | Yanina (Contabilidad) | Gestión financiera, pagos, descuentos, control de asistencia de docentes para planilla |
| `docente` | Profesores | Visualización de horario. No registran su propia asistencia en el sistema |
| `padre` | Padres / Apoderados | Portal para ver notas, asistencias, incidencias y estado de cuenta de sus hijos |

---

## Stack Tecnológico

### Backend — Laravel 11 (API)

| Componente | Tecnología |
|---|---|
| Lenguaje | PHP 8.2+ |
| Framework | Laravel 11 (API Mode) |
| Arquitectura | Clean Architecture + Vertical Slice (Modules) |
| Autenticación | Laravel Sanctum |
| Base de datos | PostgreSQL 16 |
| ORM | Eloquent ORM + Query Builder |
| Migraciones | Laravel Migrations |
| Roles y permisos | Spatie Laravel Permission |
| Correos | Laravel Mail + SMTP |
| Testing | Pest PHP |
| Documentación API | Laravel Scribe |

### Frontend — React 18 + Vite (SPA)

| Componente | Tecnología |
|---|---|
| Framework | React 18 |
| Bundler | Vite |
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
| Servidor | Hetzner CX32 — 4 vCPU, 8 GB RAM, 80 GB SSD |
| OS | Ubuntu 22.04 LTS |
| Web server | Nginx |
| PHP runtime | PHP-FPM 8.2 |
| Base de datos | PostgreSQL 16 (instalación directa) |
| SSL | Let's Encrypt |
| CI/CD | GitHub Actions → SSH deploy |
| Colas Laravel | Supervisor |

---

## Módulos del Sistema

| # | Módulo | Descripción |
|---|---|---|
| 1 | Asistencia (Alumnos) | Registro de ingreso/salida, tardanzas, faltas, notificaciones por correo, justificaciones |
| 2 | Asistencia y Planilla (Docentes) | Control de tardanzas y faltas docentes, cálculo de descuentos para planilla |
| 3 | Finanzas y Pagos | Gestión de matrícula, mensualidades, descuento por pronto pago, becas |
| 4 | Evaluación y Academia | Exámenes semanales (viernes), publicación (martes), notas y rankings |
| 5 | Incidencias y Psicología | Cuaderno de Incidencias virtual, derivación TOE → Psicología, atenciones confidenciales |
| 6 | Materiales | Separatas, videos y recursos por curso para alumnos |
| 7 | Horarios | Horarios por grado y sección, calendario académico |
| 8 | Comunicados | Avisos oficiales con notificación por correo electrónico |

---

## Estructura del Proyecto (Monorepo)

```
CienciasNET/                   # Raíz del Repositorio de Git
├── backend/                   # Laravel 11 (API REST)
│   ├── app/
│   │   ├── Modules/           # Vertical Slicing (Reglas de Negocio)
│   │   │   ├── Auth/
│   │   │   ├── Asistencia/
│   │   │   ├── Finanzas/
│   │   │   ├── Academico/
│   │   │   ├── TOE/
│   │   │   ├── Usuarios/
│   │   │   ├── Materiales/
│   │   │   ├── Horarios/
│   │   │   └── Comunicados/
│   │   └── Shared/            # Traits, Excepciones y Respuestas Base
│   ├── config/
│   ├── database/
│   │   ├── migrations/        # Migraciones de PostgreSQL (UUIDs)
│   │   └── seeders/           # Seeders de Spatie y usuarios iniciales
│   ├── routes/
│   │   └── api.php            # Todas las rutas consumibles protegidas por Sanctum
│   ├── composer.json
│   └── .env
│
├── frontend/                  # React 18 + Vite (SPA)
│   ├── public/                # Assets estáticos
│   ├── src/
│   │   ├── assets/            # Imágenes, logotipos, estilos globales
│   │   ├── components/
│   │   │   ├── ui/            # Componentes reutilizables (shadcn/ui)
│   │   │   └── shared/        # Sidebar, Navbar, modales globales
│   │   ├── features/          # Lógica frontend por dominio
│   │   │   ├── auth/          # Login y recuperación
│   │   │   ├── asistencia/    # Vistas del Auxiliar
│   │   │   ├── finanzas/      # Panel de Yanina y visualización Padre
│   │   │   ├── academico/     # Interfaz del Coordinador Académico
│   │   │   ├── toe/           # Incidencias y derivaciones
│   │   │   ├── materiales/    # Recursos por curso
│   │   │   ├── horarios/      # Horarios y calendario
│   │   │   └── comunicados/   # Avisos institucionales
│   │   ├── hooks/             # Custom hooks (useAuth, usePermissions)
│   │   ├── lib/               # Cliente HTTP (Axios interceptors)
│   │   ├── routes/            # Configuración de React Router (Rutas protegidas)
│   │   ├── store/             # Estado global (Zustand)
│   │   ├── types/             # Tipado global de TypeScript (DTOs del backend)
│   │   ├── App.tsx
│   │   └── main.tsx
│   ├── package.json
│   ├── tailwind.config.js
│   └── vite.config.ts
│
├── .gitignore
└── README.md
```

**Flujo de Comunicación (CORS y API):** En desarrollo, el frontend (`localhost:5173`) hace peticiones al backend (`localhost:8000`). En producción, Nginx sirve los estáticos del build de Vite y actúa como reverse proxy a la API de Laravel. Axios usa `withCredentials: true` para tokens CSRF de Sanctum.

---

## Estado del Proyecto

| Fase | Estado |
|---|---|
| Documentación y arquitectura | ✅ Completado |
| Setup Laravel + React/Vite | ⬜ Pendiente |
| Migraciones PostgreSQL | ⬜ Pendiente |
| Módulo Auth y Roles (Spatie) | ⬜ Pendiente |
| Módulo Asistencia Alumnos | ⬜ Pendiente |
| Módulo Asistencia Docentes | ⬜ Pendiente |
| Módulo Finanzas | ⬜ Pendiente |
| Módulo Evaluación | ⬜ Pendiente |
| Módulo Incidencias y Psicología | ⬜ Pendiente |
| Módulo Materiales | ⬜ Pendiente |
| Módulo Horarios | ⬜ Pendiente |
| Módulo Comunicados | ⬜ Pendiente |
| Deploy en VPS Hetzner | ⬜ Pendiente |
