# Modelo de Datos — CienciasNET (PostgreSQL 16)

Todas las tablas usan **UUID** como clave primaria. Las fechas con zona horaria usan `TIMESTAMPTZ` para respetar UTC-5 (Lima).

---

## Diagrama de Relaciones

```
users (1)──────────────────► alumno_perfiles (1)
users (1)──────────────────► padre_perfiles (1)
users (1)──────────────────► docente_perfiles (1)

alumno_perfiles ◄──── alumno_padre ────► padre_perfiles  (N:M)
alumno_perfiles ◄──── matriculas ────► grupos            (N:M)

grupos ──► horarios ──► cursos + docente_perfiles
grupos ──► sesiones ──► cursos + docente_perfiles
sesiones ──► asistencias ──► alumno_perfiles

evaluaciones (grupo + curso) ──► notas ──► alumno_perfiles

alumno_perfiles ──► pagos
materiales ──► cursos + docente_perfiles
comunicados (publicado por admin/director)
audit_logs (registro de acciones críticas)
```

---

## Tablas Detalladas

### `users` (base de Laravel)
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | `gen_random_uuid()` |
| nombre | VARCHAR(150) | Nombre completo |
| email | VARCHAR(191) UNIQUE | |
| email_verified_at | TIMESTAMPTZ | Nullable |
| password | VARCHAR(255) | Hash bcrypt |
| activo | BOOLEAN DEFAULT true | |
| ultimo_login | TIMESTAMPTZ | Nullable |
| remember_token | VARCHAR(100) | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `alumno_perfiles`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users UNIQUE | |
| dni | VARCHAR(8) UNIQUE | |
| fecha_nacimiento | DATE | |
| telefono | VARCHAR(15) | Nullable |
| foto_ruta | VARCHAR(500) | Ruta relativa en /storage/app/public/fotos/ |
| universidad_objetivo | VARCHAR(200) | Nullable |
| carrera_objetivo | VARCHAR(200) | Nullable |
| puntaje_objetivo | SMALLINT | Puntaje histórico de ingreso |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

> **Sobre `foto_ruta`:** se guarda solo la ruta relativa (ej: `fotos/abc123.webp`), no la URL completa. Laravel construye la URL pública al servir la imagen.

---

### `padre_perfiles`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users UNIQUE | |
| dni | VARCHAR(8) | Nullable |
| telefono | VARCHAR(15) | Nullable |
| relacion | VARCHAR(20) | padre / madre / apoderado |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `alumno_padre` (pivote N:M)
| Campo | Tipo | Notas |
|---|---|---|
| alumno_id | UUID FK → alumno_perfiles | |
| padre_id | UUID FK → padre_perfiles | |
| PK | (alumno_id, padre_id) | |

---

### `docente_perfiles`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users UNIQUE | |
| dni | VARCHAR(8) | Nullable |
| telefono | VARCHAR(15) | Nullable |
| especialidad | VARCHAR(100) | Nullable |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `grupos`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| nombre | VARCHAR(100) | Ej: "Grupo A - Mañana" |
| ciclo | VARCHAR(30) | Anual / Intensivo / Semestral |
| turno | VARCHAR(20) | mañana / tarde / noche |
| sede | VARCHAR(100) | Nullable |
| anno | SMALLINT | |
| activo | BOOLEAN DEFAULT true | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `cursos`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| nombre | VARCHAR(100) | Ej: "Matemática", "Física" |
| codigo | VARCHAR(20) UNIQUE | |
| area | VARCHAR(20) | ciencias / letras / general |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `matriculas`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumno_perfiles | |
| grupo_id | UUID FK → grupos | |
| fecha_matricula | DATE | |
| estado | VARCHAR(20) | activa / retirada / suspendida |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |
| UNIQUE | (alumno_id, grupo_id) | Sin duplicados |

---

### `sesiones`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| grupo_id | UUID FK → grupos | |
| curso_id | UUID FK → cursos | |
| docente_id | UUID FK → docente_perfiles | |
| fecha | DATE | |
| hora_inicio | TIME | |
| hora_fin | TIME | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `evaluaciones`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| grupo_id | UUID FK → grupos | |
| curso_id | UUID FK → cursos | |
| docente_id | UUID FK → docente_perfiles | |
| tipo | VARCHAR(20) | fast_test / semanal / simulacro |
| titulo | VARCHAR(200) | |
| fecha | DATE | |
| puntaje_maximo | NUMERIC(5,2) | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `notas`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumno_perfiles | |
| evaluacion_id | UUID FK → evaluaciones | |
| puntaje | NUMERIC(5,2) | |
| observacion | TEXT | Nullable |
| registrado_por | UUID FK → users | Auditoría |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |
| UNIQUE | (alumno_id, evaluacion_id) | |

---

### `asistencias`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumno_perfiles | |
| sesion_id | UUID FK → sesiones | |
| estado | VARCHAR(25) | presente / tardanza / falta_justificada / falta_injustificada |
| observacion | TEXT | Nullable |
| registrado_por | UUID FK → users | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |
| UNIQUE | (alumno_id, sesion_id) | |

---

### `pagos`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumno_perfiles | |
| concepto | VARCHAR(200) | Matrícula, Mensualidad, etc. |
| monto_total | NUMERIC(8,2) | |
| monto_pagado | NUMERIC(8,2) DEFAULT 0 | |
| fecha_vencimiento | DATE | |
| fecha_pago | DATE | Nullable |
| estado | VARCHAR(20) | pendiente / pagado / vencido |
| comprobante_ruta | VARCHAR(500) | Ruta en /storage/app/public/comprobantes/ |
| registrado_por | UUID FK → users | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `materiales`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| curso_id | UUID FK → cursos | |
| docente_id | UUID FK → docente_perfiles | |
| grupo_id | UUID FK → grupos | Nullable = aplica a todos |
| titulo | VARCHAR(200) | |
| tipo | VARCHAR(20) | pdf / video / enlace / otro |
| ruta_o_url | VARCHAR(500) | Ruta local si es archivo, URL si es enlace externo |
| es_archivo_local | BOOLEAN DEFAULT true | false = es enlace externo |
| semana | SMALLINT | Nullable |
| activo | BOOLEAN DEFAULT true | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `comunicados`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| titulo | VARCHAR(200) | |
| contenido | TEXT | |
| publicado_por | UUID FK → users | |
| destinatarios | JSONB | {"roles": ["padre","alumno"], "grupos": []} |
| importante | BOOLEAN DEFAULT false | |
| fecha_publicacion | TIMESTAMPTZ | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `horarios`
| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| grupo_id | UUID FK → grupos | |
| curso_id | UUID FK → cursos | |
| docente_id | UUID FK → docente_perfiles | |
| dia_semana | SMALLINT | 1=Lun … 7=Dom |
| hora_inicio | TIME | |
| hora_fin | TIME | |
| aula | VARCHAR(50) | Nullable |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `audit_logs`
| Campo | Tipo | Notas |
|---|---|---|
| id | BIGSERIAL PK | Incremental, no UUID (volumen alto) |
| user_id | UUID FK → users | Nullable |
| action | VARCHAR(100) | Ej: nota.actualizada |
| model | VARCHAR(100) | Ej: Nota |
| model_id | VARCHAR(36) | UUID del registro afectado |
| old_values | JSONB | Nullable |
| new_values | JSONB | Nullable |
| ip | VARCHAR(45) | |
| created_at | TIMESTAMPTZ | |

---

## Configuración en Laravel (.env)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cienciasnet
DB_USERNAME=cienciasnet_user
DB_PASSWORD=tu_password_seguro
```

## Configuración de almacenamiento local (config/filesystems.php)

```php
'disks' => [
    'local_publico' => [
        'driver' => 'local',
        'root'   => storage_path('app/public'),
        'url'    => env('APP_URL') . '/api/archivos',
        'visibility' => 'private', // acceso solo via controlador autenticado
    ],
],
```

## Ventajas de PostgreSQL para este proyecto

| Característica | Uso en CienciasNET |
|---|---|
| **JSONB** | Columna `destinatarios` en comunicados y auditoría |
| **UUID nativo** | PKs de todas las tablas sin autoincrement |
| **TIMESTAMPTZ** | Fechas con zona horaria UTC-5 Lima |
| **NUMERIC(x,y)** | Notas y montos sin errores de punto flotante |
| **Full-text search** | Búsqueda de alumnos por nombre sin extensiones |
| **Índices parciales** | Solo matrículas activas, pagos pendientes |
