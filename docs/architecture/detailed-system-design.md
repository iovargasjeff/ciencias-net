# Arquitectura — CienciasNET (Colegio Ciencias)

> **Referencia técnica detallada vigente.** Conserva diagramas, contratos, matriz de acceso, estructura modular y árbol
> esperado del repositorio. Los documentos especializados de `docs/architecture/` aclaran o amplían sus secciones.

## Stack: Laravel 13 API + React / Vite SPA

Arquitectura desacoplada tipo Monorepo. Laravel expone una API REST consumida por React (SPA con Vite). La misma API
puede servir en el futuro a una app móvil.

---

## Diagrama General

```
┌──────────────────────────────────────────────────────────────┐
│                      VPS Hetzner CX32                        │
│                  Ubuntu 22.04 LTS                            │
│                                                              │
│  ┌──────────────────┐   HTTPS    ┌────────────────────────┐  │
│  │   Nginx (static) │ ◄───────►  │   Laravel 13 API       │  │
│  │   React build    │            │   PHP-FPM 8.3          │  │
│  │   /dist/         │            └──────┬──────┬──────┬───┘  │
│  └──────────────────┘                   │      │      │ S3 API│
│                              ┌──────────▼───┐  │  ┌───▼─────┐│
│                              │ PostgreSQL 16│  │  │R2 privado││
│                              │ puerto 5432  │  │  │biometría ││
│                              │ red privada  │  │  └─────────┘│
│                              └──────────────┘  │ privada      │
│                                         ┌─────▼──────────┐   │
│                                         │ Python FastAPI │   │
│                                         │ servicio facial│   │
│                                         └────────────────┘   │
│                                                              │
│  ┌──────────────────────────────────────────────────────┐    │
│  │  Nginx — Reverse Proxy + SSL (Let's Encrypt)         │    │
│  │  cienciascolegio.pe        → React build (static)    │    │
│  │  api.cienciascolegio.pe    → PHP-FPM (Laravel)       │    │
│  │  Servicio facial Python    → solo red privada         │    │
│  └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

---

## Integración de Reconocimiento Facial en Puerta

La asistencia facial opera como un control de acceso en las entradas y salidas autorizadas. Puede utilizar múltiples
estaciones web: celulares/tablets con cámara o PCs con una o varias cámaras. El servicio facial es un componente
especializado y no contiene reglas de negocio de asistencia.

```text
Estaciones web autorizadas
    │ captura puntual + sesión técnica
    ▼
API Laravel ── red privada ──► Servicio facial Python (VPS)
    │                              │
    │◄── persona identificada + confianza
    ▼
Reglas de asistencia
                                      │
                       ┌──────────────┼──────────────┐
                       ▼              ▼              ▼
                  PostgreSQL    Cola de correo   R2 privado
                                                 (enrolamiento y
                                                  evidencia excepcional)
```

### Responsabilidades por Componente

| Componente             | Responsabilidad                                                                 |
|------------------------|---------------------------------------------------------------------------------|
| Estación web            | Usar cámara(s) autorizadas desde el navegador y enviar capturas a Laravel      |
| Servicio facial Python | Validar calidad/prueba de vida, generar embedding, identificar y dar confianza  |
| Laravel                | Autenticar estación, enviar captura a Python y decidir asistencia/auditoría     |
| PostgreSQL             | Guardar perfiles biométricos cifrados, eventos, asistencias y consentimientos   |
| Cloudflare R2 privado  | Guardar fotos de enrolamiento y evidencia excepcional con retención limitada    |
| Auxiliar / Yanina      | Resolver excepciones de alumnos / docentes respectivamente                      |

La estación web no transmite video continuo. Captura imágenes puntuales y utiliza una clave de idempotencia para evitar
duplicados. Si no tiene Internet, muestra que el registro está temporalmente no disponible y se utiliza el flujo manual;
el navegador no conserva capturas biométricas para enviarlas después.

### Activación y acceso a la estación web

La pantalla de asistencia es una ruta web limitada y no utiliza el login de una persona. El flujo es:

1. Desde una PC segura, una cuenta humana con `gestionar_dispositivos` crea una estación web.
2. Laravel genera un código o QR de activación de un solo uso, con expiración máxima de 10 minutos.
3. En la PC, tablet o celular destinado a asistencia se abre la ruta web de activación y se escanea/ingresa el código.
4. Laravel crea una sesión técnica exclusiva para ese navegador, sin copiar la sesión personal del responsable.
5. El responsable autoriza y selecciona las cámaras disponibles en ese equipo.
6. Desde entonces el navegador abre la pantalla de captura y solo puede enviar eventos con los permisos de la estación.

La URL de activación puede ser accesible desde Internet, pero sin un código vigente no activa ninguna estación. La URL
de captura puede cargar la interfaz, pero Laravel rechaza todo evento sin sesión técnica válida. La estación no
puede consultar alumnos, perfiles faciales, notas, pagos ni paneles administrativos.

Para tablets y celulares se configura modo kiosco, PIN de salida administrado por el colegio, bloqueo de instalación de
aplicaciones y actualizaciones automáticas. Si alguien roba, desbloquea o manipula el equipo, el responsable desactiva el
estación desde el panel, rota su credencial y revisa sus eventos recientes. La prueba de vida, límites de frecuencia,
idempotencia y revisión humana reducen el riesgo de eventos falsos, pero una estación comprometida siempre debe
considerarse no confiable hasta ser revocado y enrolado nuevamente.

Una estación web puede ser un celular/tablet con una cámara o una PC con una o varias cámaras reconocidas por el
navegador. Cada cámara queda registrada con nombre, ubicación y modo, pero la sesión técnica pertenece a la estación web.
La pantalla de captura permanece abierta para estudiantes y docentes; solo activar, cambiar cámaras o cerrar la estación
requiere intervención de una cuenta autorizada.

### Contratos de captura e integración

La estación web envía la captura puntual a Laravel usando su sesión técnica:

```http
POST /api/estacion/asistencia/capturas
Cookie: estacion_session=<cookie-httpOnly>
Idempotency-Key: puerta-principal-20260606-074012-123
```

Laravel valida la estación/cámara y envía la imagen al servicio Python mediante la red privada. Python devuelve solamente
la identificación, confianza y resultado de prueba de vida; Laravel crea el evento y movimiento correspondiente.

```json
{
  "persona_id": "uuid",
  "tipo_persona": "alumno",
  "confianza": 0.94,
  "prueba_vida_superada": true,
  "estacion_codigo": "puerta-principal-pc",
  "camara_codigo": "entrada-izquierda",
  "capturado_en": "2026-06-06T07:40:12-05:00"
}
```

Laravel valida la estación, cámara e idempotencia antes de crear un movimiento de asistencia. El consentimiento se valida
al enrolar; el servicio facial solo recibe perfiles activos. Laravel determina si corresponde ingreso, salida o
reingreso según el modo de la cámara, la configuración y el historial del día. Los eventos con confianza intermedia
o baja quedan pendientes de revisión.

El servicio facial no es accesible desde el navegador ni consulta PostgreSQL directamente. Laravel le entrega por un
endpoint interno autenticado los
perfiles activos necesarios; Python los mantiene únicamente en memoria y solicita una resincronización cuando cambian.

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

> El árbol siguiente conserva el detalle esperado de implementación. La organización documental y los OpenSpec separados
> vigentes están definidos en [`system.md`](system.md); esa estructura prevalece para `docs/`, `backend/openspec/` y
> `frontend/openspec/`.

```
CienciasNET/
│
├── backend/                              ← Laravel 13 API
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
│   │   │   ├── 2025_01_01_000008_create_consentimientos_biometricos_table.php
│   │   │   ├── 2025_01_01_000009_create_perfiles_faciales_table.php
│   │   │   ├── 2025_01_01_000010_create_archivos_biometricos_table.php
│   │   │   ├── 2025_01_01_000011_create_estaciones_biometricas_table.php
│   │   │   ├── 2025_01_01_000012_create_eventos_reconocimiento_table.php
│   │   │   ├── 2025_01_01_000013_create_movimientos_asistencia_table.php
│   │   │   ├── 2025_01_01_000014_create_configuraciones_jornada_table.php
│   │   │   ├── 2025_01_01_000015_create_tarifas_docentes_table.php
│   │   │   ├── 2025_01_01_000016_create_liquidaciones_descuento_docentes_table.php
│   │   │   ├── 2025_01_01_000017_create_conceptos_pago_table.php
│   │   │   ├── 2025_01_01_000018_create_beneficios_alumnos_table.php
│   │   │   ├── 2025_01_01_000019_create_obligaciones_pago_table.php
│   │   │   ├── 2025_01_01_000020_create_movimientos_pago_table.php
│   │   │   ├── 2025_01_01_000021_create_incidencias_table.php
│   │   │   ├── 2025_01_01_000022_create_atenciones_psicologia_table.php
│   │   │   ├── 2025_01_01_000023_create_examenes_table.php
│   │   │   ├── 2025_01_01_000024_create_notas_table.php
│   │   │   ├── 2025_01_01_000025_create_materiales_table.php
│   │   │   ├── 2025_01_01_000026_create_horarios_table.php
│   │   │   ├── 2025_01_01_000027_create_comunicados_table.php
│   │   │   └── 2025_01_01_000028_create_audit_logs_table.php
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
├── frontend/                             ← React + Vite SPA
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
├── facial-service/                       ← Python + FastAPI
│   ├── app/
│   │   ├── api/                          # Endpoints de captura y salud
│   │   ├── recognition/                  # Calidad, prueba de vida y comparación
│   │   └── clients/                      # Comunicación autenticada con Laravel
│   ├── tests/
│   ├── Dockerfile
│   └── requirements.txt
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
migración. Las tablas de dominio usan UUID; `audit_logs` usa `BIGSERIAL` por volumen.

La lista del árbol es ilustrativa. Antes de implementar se debe regenerar el orden completo sin timestamps duplicados:
primero usuarios y estructura académica (`periodos_academicos`, `grados`, `secciones`, `matriculas`, `cursos`,
`carga_academica`); luego cuentas técnicas, biometría y asistencia; después finanzas, incidencias, evaluación,
calendario, comunicaciones y auditoría. También requieren migración propia `anomalias_asistencia`, `sesiones_clase`,
`configuraciones_financieras`, `cuentas_tecnicas`, `estaciones_biometricas`, `camaras_estacion`,
`activaciones_estacion`, `historial_incidencias`, `reportes_academicos`,
`eventos_calendario`, `comunicado_lecturas` y `notificaciones`.

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
Usuarios humanos (React SPA):
1. GET /sanctum/csrf-cookie
2. POST /api/auth/login { email, password }
3. Laravel crea sesión mediante cookie httpOnly, Secure y SameSite
4. React usa withCredentials; nunca guarda un Bearer token en localStorage
5. Middleware y Policy verifican contexto, permiso y acceso al recurso

Servicios y estaciones web:
1. Un administrador autorizado genera un código de activación temporal de un solo uso
2. El navegador activado recibe una sesión técnica separada con scopes mínimos
3. Sanctum valida la sesión técnica, estación activa e idempotencia
4. Las cuentas técnicas no pueden iniciar sesión en los paneles humanos
```

Sanctum se usa en dos modos deliberadamente separados: sesión/cookie para personas y Bearer token para integraciones.
Una persona con varios roles usa la misma cuenta y selecciona el contexto de portal correspondiente después del login.

---

## Matriz de Roles y Acceso

| Módulo                  | superadmin | gestor_usuarios | toe | psicologia | auxiliar | coord_acad | administrativo | docente      | padre   | alumno |
|-------------------------|------------|-----------------|-----|------------|----------|------------|----------------|--------------|---------|--------|
| Gestión de usuarios     | ✅          | ✅ limitada      | ❌   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Asignar superadmin      | ✅          | ❌               | ❌   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Gestionar estaciones    | ✅          | ❌               | ❌   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Asistencia alumnos      | ✅          | ❌               | 👁️ | ❌          | ✅        | 👁️        | ❌              | ❌            | 👁️ hijo  | 👁️ propia |
| Justificar faltas       | ✅          | ❌               | ✅   | ❌          | ✅        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Configurar jornada      | ✅          | ❌               | ❌   | ❌          | ❌        | ✅          | ❌              | ❌            | ❌       | ❌      |
| Asistencia docentes     | ✅          | ❌               | ❌   | ❌          | ❌        | ❌          | ✅ específica   | ❌            | ❌       | ❌      |
| Finanzas (pagos)        | ✅          | ❌               | ❌   | ❌          | ❌        | ❌          | ✅ específica   | ❌            | 👁️ hijo  | 👁️ propia |
| Exámenes (crear)        | ✅          | ❌               | ❌   | ❌          | ❌        | ✅          | ❌              | ❌            | ❌       | ❌      |
| Notas (registrar)       | ✅          | ❌               | ❌   | ❌          | ❌        | ✅          | ❌              | ✅ asignadas   | ❌       | ❌      |
| Ver notas               | ✅          | ❌               | 👁️ | 👁️        | 👁️      | ✅          | ❌              | 👁️ asignadas | 👁️ hijo  | 👁️ propia |
| Incidencias (registrar) | ✅          | ❌               | ✅   | ❌          | ✅        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Derivar a Psicología    | ✅          | ❌               | ✅   | ❌          | ❌        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Atenciones psicología   | ✅          | ❌               | ❌   | ✅          | ❌        | ❌          | ❌              | ❌            | ❌       | ❌      |
| Materiales              | ✅          | ❌               | ❌   | ❌          | ❌        | ✅          | ❌              | ✅            | ✅ ver   | ✅ ver  |
| Horarios                | ✅          | ❌               | 👁️ | 👁️        | 👁️      | ✅          | ❌              | ✅ ver        | ✅ ver   | ✅ ver  |
| Comunicados             | ✅          | ❌               | ✅   | ✅          | 👁️      | ✅          | 👁️            | 👁️          | ✅       | ✅      |
| Reportes globales       | ✅          | ❌               | ❌   | ❌          | ❌        | ✅ académicos | ✅ específica | ❌            | ❌       | ❌      |

> 👁️ = Solo lectura / consulta. ✅ = Lectura y escritura. `✅ específica` exige permiso asignado a una cuenta concreta,
> no a todo el rol. `✅ limitada` permite administrar cuentas y roles operativos,
> pero nunca crear/asignar `superadmin`, cambiar sus propios roles o acceder a módulos funcionales.
> `superadmin` puede delegar `gestionar_dispositivos` a una cuenta concreta sin convertirla en superadmin; ese permiso
> individual prevalece sobre la matriz general y queda auditado.

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
