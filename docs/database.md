# Modelo de Datos — CienciasNET (PostgreSQL 16)

Todas las tablas usan **UUID** como clave primaria (generada con `gen_random_uuid()`). Los roles se gestionan mediante Spatie Laravel Permission (tablas `roles`, `permissions`, `model_has_roles`). Las fechas con zona horaria usan `TIMESTAMPTZ` para UTC-5 (Lima).

---

## Diagrama de Relaciones

```
users (1)──────────────────► alumnos (1)
users (1)──────────────────► padres (1)
users (1)──────────────────► docentes (1)
users (1)──────────────────► administrativos (1)

alumnos ◄──── alumno_padre ────► padres  (N:M)

alumnos ──► asistencias_alumnos
docentes ──► asistencias_docentes

alumnos ──► pagos ──► conceptos_pago
alumnos ──► incidencias
incidencias ──► atenciones_psicologia

examenes ──► notas ──► alumnos

docentes ──► materiales ──► cursos (implícito por grado)
grupos/secciones ──► horarios
users ──► comunicados
```

---

## Tablas Detalladas

### `users` (base de Laravel + Spatie)

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | `gen_random_uuid()` |
| email | VARCHAR(191) UNIQUE | |
| password | VARCHAR(255) | Hash bcrypt |
| activo | BOOLEAN DEFAULT true | |
| ultimo_login | TIMESTAMPTZ | Nullable |
| remember_token | VARCHAR(100) | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

> Los roles y permisos se manejan con las tablas `roles`, `permissions`, `model_has_roles` del paquete Spatie. No hay columna `role` en `users`.

---

### `alumnos`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users.id UNIQUE | |
| dni | VARCHAR(8) UNIQUE | |
| nombres | VARCHAR(150) | |
| apellidos | VARCHAR(150) | |
| grado | INTEGER | 1 al 5 (secundaria) |
| seccion | VARCHAR(10) | |
| condicion_pago | ENUM('normal','becado','descuento') | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `padres`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users.id UNIQUE | |
| dni | VARCHAR(8) | |
| nombres | VARCHAR(150) | |
| apellidos | VARCHAR(150) | |
| celular | VARCHAR(15) | |
| correo_notificaciones | VARCHAR(191) | Crucial para envío de reportes por correo |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `alumno_padre` (Intermedia N:M)

| Campo | Tipo | Notas |
|---|---|---|
| alumno_id | UUID FK → alumnos.id | |
| padre_id | UUID FK → padres.id | |
| relacion | VARCHAR(20) | Padre, Madre, Apoderado |
| PK | (alumno_id, padre_id) | Compuesta |

---

### `docentes`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users.id UNIQUE | |
| dni | VARCHAR(8) UNIQUE | |
| nombres | VARCHAR(150) | |
| apellidos | VARCHAR(150) | |
| telefono | VARCHAR(15) | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `administrativos`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| user_id | UUID FK → users.id UNIQUE | |
| nombres | VARCHAR(150) | |
| cargo | ENUM('promotor','directora','toe','psicologa','auxiliar','coordinador_acad','yanina') | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

## Asistencias

### `asistencias_alumnos`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumnos.id | |
| fecha | DATE | |
| hora_ingreso | TIME | Nullable |
| hora_salida | TIME | Nullable |
| estado | ENUM('presente','tardanza','falta_injustificada','falta_justificada') | |
| notificacion_enviada | BOOLEAN DEFAULT false | |
| registrado_por | UUID FK → users.id | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `asistencias_docentes` (Control de Yanina)

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| docente_id | UUID FK → docentes.id | |
| fecha | DATE | |
| hora_ingreso | TIME | Nullable |
| hora_salida | TIME | Nullable |
| estado | ENUM('presente','falta_justificada','falta_injustificada') | |
| minutos_tardanza | INTEGER DEFAULT 0 | Acumulable a fin de mes |
| horas_descuento_calculado | INTEGER DEFAULT 0 | Falta injustificada = horas × 2 |
| docente_sustituto_id | UUID FK → docentes.id | Nullable. Si las horas fueron cubiertas |
| registrado_por | UUID FK → users.id | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

## Pagos y Finanzas

### `conceptos_pago`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| nombre | VARCHAR(200) | Matrícula 2026, Mensualidad Abril, Cuota de Ingreso |
| monto_base | NUMERIC(8,2) | 480.00 |
| es_mensualidad | BOOLEAN DEFAULT false | Activa regla de descuento S/ 30 |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `pagos`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumnos.id | |
| concepto_id | UUID FK → conceptos_pago.id | |
| monto_requerido | NUMERIC(8,2) | Monto con beca/descuento ya aplicado |
| descuento_pronto_pago_aplicado | BOOLEAN DEFAULT false | S/ 30 si paga hasta fin de mes |
| monto_final_pagado | NUMERIC(8,2) DEFAULT 0.00 | |
| fecha_vencimiento | DATE | |
| fecha_pago | TIMESTAMPTZ | Nullable |
| estado | ENUM('pendiente','pagado','vencido') | |
| registrado_por | UUID FK → users.id | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

## Incidencias y Psicología

### `incidencias` (Cuaderno Virtual)

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| alumno_id | UUID FK → alumnos.id | |
| reportado_por | UUID FK → users.id | Auxiliar o TOE |
| fecha | TIMESTAMPTZ | |
| tipo | ENUM('conducta','tardanza_constante','academico','otro') | |
| descripcion | TEXT | |
| derivado_psicologia | BOOLEAN DEFAULT false | |
| estado | ENUM('abierto','notificado_padre','resuelto') | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `atenciones_psicologia` (Registro Privado)

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| incidencia_id | UUID FK → incidencias.id | Nullable |
| alumno_id | UUID FK → alumnos.id | |
| psicologa_id | UUID FK → users.id | |
| fecha_atencion | TIMESTAMPTZ | |
| notas_privadas | TEXT | Confidencial. Solo Psicología y Dirección |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

## Exámenes y Notas

### `examenes`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| titulo | VARCHAR(200) | Semanal 1 - I Bimestre |
| fecha_aplicacion | DATE | Siempre viernes |
| grado_dirigido | INTEGER | 1 al 5 |
| canal | ENUM('general','ciencias','letras') | 5° se divide por canales |
| total_preguntas | INTEGER | 40 (1°-4°) o 60 (5°) |
| publicado | BOOLEAN DEFAULT false | Se activa el martes |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `notas`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| examen_id | UUID FK → examenes.id | |
| alumno_id | UUID FK → alumnos.id | |
| puntaje | NUMERIC(5,2) | |
| puesto_ranking | INTEGER | Nullable. Calculado al publicar |
| registrado_por | UUID FK → users.id | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |
| UNIQUE | (examen_id, alumno_id) | |

---

## Tablas Adaptadas (Materiales, Horarios, Comunicados)

### `materiales`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| titulo | VARCHAR(200) | |
| descripcion | TEXT | Nullable |
| tipo | VARCHAR(20) | pdf / video / enlace / otro |
| ruta_o_url | VARCHAR(500) | Ruta local si es archivo, URL si es externo |
| grado_dirigido | INTEGER | 1 al 5. NULL = todos |
| seccion | VARCHAR(10) | Nullable = todas las secciones |
| subido_por | UUID FK → users.id | Coordinador o Docente |
| semana | SMALLINT | Nullable |
| activo | BOOLEAN DEFAULT true | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `horarios`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| grado | INTEGER | 1 al 5 |
| seccion | VARCHAR(10) | |
| docente_id | UUID FK → docentes.id | |
| dia_semana | SMALLINT | 1=Lun … 7=Dom |
| hora_inicio | TIME | |
| hora_fin | TIME | |
| aula | VARCHAR(50) | Nullable |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

### `comunicados`

| Campo | Tipo | Notas |
|---|---|---|
| id | UUID PK | |
| titulo | VARCHAR(200) | |
| contenido | TEXT | |
| publicado_por | UUID FK → users.id | |
| destinatarios | JSONB | `{"roles": ["padre","docente"], "grados": [1,2,3]}` |
| importante | BOOLEAN DEFAULT false | Dispara notificación por correo |
| fecha_publicacion | TIMESTAMPTZ | |
| created_at | TIMESTAMPTZ | |
| updated_at | TIMESTAMPTZ | |

---

### `audit_logs`

| Campo | Tipo | Notas |
|---|---|---|
| id | BIGSERIAL PK | Incremental, no UUID (volumen alto) |
| user_id | UUID FK → users.id | Nullable |
| action | VARCHAR(100) | Ej: nota.actualizada, pago.registrado |
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

---

## Ventajas de PostgreSQL para este proyecto

| Característica | Uso en CienciasNET |
|---|---|
| **JSONB** | Columna `destinatarios` en comunicados y `audit_logs` |
| **UUID nativo** | PKs de todas las tablas sin autoincrement |
| **TIMESTAMPTZ** | Fechas con zona horaria UTC-5 Lima |
| **NUMERIC(x,y)** | Notas y montos sin errores de punto flotante |
| **Full-text search** | Búsqueda de alumnos por nombre sin extensiones |
| **Índices parciales** | Pagos pendientes, incidencias abiertas |
| **ENUM nativo** | Estados normalizados (asistencias, pagos, incidencias) |
