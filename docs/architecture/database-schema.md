# Modelo de Datos — CienciasNET (PostgreSQL 16)

> **Esquema lógico detallado vigente.** Define tablas, campos, relaciones, restricciones e índices esperados. Antes de
> implementar, cada grupo de tablas debe convertirse en migrations y specs backend verificadas.

Las tablas de dominio usan **UUID** como clave primaria (generada con `gen_random_uuid()`); `audit_logs` usa `BIGSERIAL`
por su volumen. Los roles se gestionan mediante
Spatie Laravel Permission (tablas `roles`, `permissions`, `model_has_roles`). Las fechas con zona horaria usan
`TIMESTAMPTZ` para UTC-5 (Lima).

---

## Diagrama de Relaciones

```
users (1)──────────────────► alumnos (1)
users (1)──────────────────► padres (1)
users (1)──────────────────► docentes (1)
users (1)──────────────────► administrativos (1)

alumnos ◄──── alumno_padre ────► padres  (N:M)
periodos_academicos ──► grados ──► secciones ──► matriculas ◄── alumnos
cursos + secciones + docentes ──► carga_academica ──► horarios

alumnos ──► asistencias_alumnos
docentes ──► asistencias_docentes
asistencias_alumnos / asistencias_docentes ──► movimientos_asistencia
movimientos_asistencia ──► anomalias_asistencia
docentes ──► tarifas_docentes ──► liquidaciones_descuento_docentes

users ──► consentimientos_biometricos
users ──► perfiles_faciales ──► archivos_biometricos
cuentas_tecnicas ──► estaciones_biometricas ──► camaras_estacion ──► eventos_reconocimiento ◄── users
eventos_reconocimiento ──► movimientos_asistencia

alumnos ──► obligaciones_pago ──► conceptos_pago
alumnos ──► beneficios_alumnos ──► obligaciones_pago
obligaciones_pago ──► movimientos_pago
alumnos ──► incidencias
incidencias ──► atenciones_psicologia

carga_academica ──► examenes ──► notas ◄── matriculas
carga_academica ──► materiales
users ──► comunicados ──► comunicado_lecturas
users ──► notificaciones
```

---

## Tablas Detalladas

### `users` (base de Laravel + Spatie)

| Campo          | Tipo                 | Notas               |
|----------------|----------------------|---------------------|
| id             | UUID PK              | `gen_random_uuid()` |
| email          | VARCHAR(191) UNIQUE  |                     |
| password       | VARCHAR(255)         | Hash bcrypt         |
| activo         | BOOLEAN DEFAULT true |                     |
| ultimo_login   | TIMESTAMPTZ          | Nullable            |
| remember_token | VARCHAR(100)         |                     |
| created_at     | TIMESTAMPTZ          |                     |
| updated_at     | TIMESTAMPTZ          |                     |

> Los roles y permisos se manejan con las tablas `roles`, `permissions`, `model_has_roles` del paquete Spatie. No hay
> columna `role` en `users`.
>
> `gestor_usuarios` es un rol delegado independiente. Solo `superadmin` puede asignarlo o retirarlo. El gestor puede
> administrar cuentas y roles operativos, pero no puede asignar `superadmin`, modificar sus propios roles ni concederse
> permisos adicionales.
>
> Cada cuenta humana tiene un correo obligatorio y único. Una persona que sea padre y trabajador conserva una sola fila
> en `users` con múltiples roles y perfiles; las rutas seleccionan el contexto y las Policies limitan cada recurso.

---

### `alumnos`

| Campo          | Tipo                                | Notas               |
|----------------|-------------------------------------|---------------------|
| id             | UUID PK                             |                     |
| user_id        | UUID FK → users.id UNIQUE           | Nullable; solo existe si tiene acceso al portal |
| dni            | VARCHAR(8) UNIQUE                   |                     |
| nombres        | VARCHAR(150)                        |                     |
| apellidos      | VARCHAR(150)                        |                     |
| created_at     | TIMESTAMPTZ                         |                     |
| updated_at     | TIMESTAMPTZ                         |                     |

---

### `padres`

| Campo                 | Tipo                      | Notas                                     |
|-----------------------|---------------------------|-------------------------------------------|
| id                    | UUID PK                   |                                           |
| user_id               | UUID FK → users.id UNIQUE |                                           |
| dni                   | VARCHAR(8)                |                                           |
| nombres               | VARCHAR(150)              |                                           |
| apellidos             | VARCHAR(150)              |                                           |
| celular               | VARCHAR(15)               |                                           |
| correo_notificaciones | VARCHAR(191)              | Crucial para envío de reportes por correo |
| created_at            | TIMESTAMPTZ               |                                           |
| updated_at            | TIMESTAMPTZ               |                                           |

Un padre solo existe como perfil registrado y siempre referencia una cuenta con correo único. `correo_notificaciones`
puede coincidir con `users.email`, pero no reemplaza el identificador de inicio de sesión.

---

### `alumno_padre` (Intermedia N:M)

| Campo     | Tipo                  | Notas                   |
|-----------|-----------------------|-------------------------|
| alumno_id | UUID FK → alumnos.id  |                         |
| padre_id  | UUID FK → padres.id   |                         |
| relacion  | VARCHAR(20)           | Padre, Madre, Apoderado |
| es_contacto_principal | BOOLEAN DEFAULT false | Puede existir más de un padre vinculado |
| recibe_notificaciones | BOOLEAN DEFAULT true | Preferencia por vínculo |
| PK        | (alumno_id, padre_id) | Compuesta               |

---

## Estructura Académica

### `periodos_academicos`

| Campo        | Tipo                                | Notas |
|--------------|-------------------------------------|-------|
| id           | UUID PK                             |       |
| nombre       | VARCHAR(100)                        | Ej. Año lectivo 2026 |
| tipo         | ENUM('colegio','academia')          | Define reglas de reportes |
| fecha_inicio | DATE                                |       |
| fecha_fin    | DATE                                |       |
| estado       | ENUM('borrador','activo','cerrado') | Solo uno activo por tipo |
| creado_por   | UUID FK → users.id                  | Coordinador Académico o superadmin |

### `grados`

| Campo                | Tipo                              | Notas |
|----------------------|-----------------------------------|-------|
| id                   | UUID PK                           |       |
| periodo_academico_id | UUID FK → periodos_academicos.id |       |
| nombre               | VARCHAR(100)                      | Ej. 3° Secundaria |
| nivel                | VARCHAR(50)                       | Secundaria, academia |
| orden                | SMALLINT                          |       |
| activo               | BOOLEAN DEFAULT true              |       |

### `secciones`

| Campo    | Tipo                                  | Notas |
|----------|---------------------------------------|-------|
| id       | UUID PK                               |       |
| grado_id | UUID FK → grados.id                   |       |
| nombre   | VARCHAR(30)                           | Ej. A, Ciencias |
| turno    | ENUM('manana','tarde','noche')        |       |
| aula     | VARCHAR(50)                           | Nullable |
| activo   | BOOLEAN DEFAULT true                  |       |

### `matriculas`

| Campo          | Tipo                                            | Notas |
|----------------|-------------------------------------------------|-------|
| id             | UUID PK                                         |       |
| alumno_id      | UUID FK → alumnos.id                            |       |
| seccion_id     | UUID FK → secciones.id                          |       |
| codigo         | VARCHAR(50) UNIQUE                              |       |
| fecha          | DATE                                            |       |
| estado         | ENUM('preinscrito','activo','retirado','trasladado','finalizado') | |
| registrado_por | UUID FK → users.id                              | Cuenta autorizada |
| UNIQUE         | (alumno_id, seccion_id)                         | Una matrícula por sección |

### `cursos`

| Campo  | Tipo                 | Notas |
|--------|----------------------|-------|
| id     | UUID PK              |       |
| codigo | VARCHAR(50) UNIQUE   |       |
| nombre | VARCHAR(150)         |       |
| area   | VARCHAR(100)         | Nullable |
| activo | BOOLEAN DEFAULT true |       |

### `carga_academica`

| Campo          | Tipo                         | Notas |
|----------------|------------------------------|-------|
| id             | UUID PK                      |       |
| seccion_id     | UUID FK → secciones.id       |       |
| curso_id       | UUID FK → cursos.id          |       |
| docente_id     | UUID FK → docentes.id        |       |
| vigente_desde  | DATE                         |       |
| vigente_hasta  | DATE                         | Nullable |
| activo         | BOOLEAN DEFAULT true         |       |
| asignado_por   | UUID FK → users.id           | Coordinador Académico |

La matrícula define a qué sección pertenece el alumno durante un periodo. La carga académica define qué docente puede
registrar notas y materiales para cada curso y sección. Un cambio de docente conserva las cargas anteriores por vigencia.

---

### `docentes`

| Campo      | Tipo                      | Notas |
|------------|---------------------------|-------|
| id         | UUID PK                   |       |
| user_id    | UUID FK → users.id UNIQUE |       |
| dni        | VARCHAR(8) UNIQUE         |       |
| nombres    | VARCHAR(150)              |       |
| apellidos  | VARCHAR(150)              |       |
| telefono   | VARCHAR(15)               |       |
| created_at | TIMESTAMPTZ               |       |
| updated_at | TIMESTAMPTZ               |       |

---

### `administrativos`

| Campo      | Tipo                                                                                  | Notas |
|------------|---------------------------------------------------------------------------------------|-------|
| id         | UUID PK                                                                               |       |
| user_id    | UUID FK → users.id UNIQUE                                                             |       |
| nombres    | VARCHAR(150)                                                                          |       |
| cargo      | ENUM('promotor','directora','toe','psicologa','auxiliar','coordinador_acad','yanina') |       |
| created_at | TIMESTAMPTZ                                                                           |       |
| updated_at | TIMESTAMPTZ                                                                           |       |

---

## Asistencias

### `asistencias_alumnos`

| Campo           | Tipo                                                                  | Notas                          |
|-----------------|-----------------------------------------------------------------------|--------------------------------|
| id              | UUID PK                                                               |                                |
| alumno_id       | UUID FK → alumnos.id                                                  |                                |
| fecha           | DATE                                                                  |                                |
| primer_ingreso  | TIME                                                                  | Nullable. Derivado de movimientos |
| ultima_salida   | TIME                                                                  | Nullable. Derivado de movimientos |
| estado          | ENUM('presente','tardanza','falta_injustificada','falta_justificada') | Resumen diario                  |
| presencia_abierta | BOOLEAN DEFAULT false                                                | Existe ingreso sin salida confirmada |
| registrado_por  | UUID FK → users.id                                                    |                                |
| created_at      | TIMESTAMPTZ                                                           |                                |
| updated_at      | TIMESTAMPTZ                                                           |                                |
| UNIQUE          | (alumno_id, fecha)                                                    | Un resumen diario               |

### `asistencias_docentes` (Control de Yanina)

| Campo                     | Tipo                                                       | Notas                                   |
|---------------------------|------------------------------------------------------------|-----------------------------------------|
| id                        | UUID PK                                                    |                                         |
| docente_id                | UUID FK → docentes.id                                      |                                         |
| fecha                     | DATE                                                       |                                         |
| primer_ingreso            | TIME                                                       | Nullable. Derivado de movimientos       |
| ultima_salida             | TIME                                                       | Nullable. Derivado de movimientos       |
| estado                    | ENUM('presente','falta_justificada','falta_injustificada') |                                         |
| minutos_tardanza          | INTEGER DEFAULT 0                                          | Acumulable a fin de mes                 |
| docente_sustituto_id      | UUID FK → docentes.id                                      | Nullable. Si las horas fueron cubiertas |
| registrado_por            | UUID FK → users.id                                         |                                         |
| created_at                | TIMESTAMPTZ                                                |                                         |
| updated_at                | TIMESTAMPTZ                                                |                                         |
| UNIQUE                    | (docente_id, fecha)                                        | Un registro diario                      |

### `movimientos_asistencia`

| Campo                    | Tipo                                           | Notas                                      |
|--------------------------|------------------------------------------------|--------------------------------------------|
| id                       | UUID PK                                        |                                            |
| asistencia_alumno_id     | UUID FK → asistencias_alumnos.id               | Nullable                                   |
| asistencia_docente_id    | UUID FK → asistencias_docentes.id              | Nullable                                   |
| tipo                     | ENUM('ingreso','salida','reingreso')            |                                            |
| motivo                   | ENUM('regular','temporal','emergencia','otro')  |                                            |
| observacion              | TEXT                                           | Obligatoria para emergencia/otro           |
| ocurrido_en              | TIMESTAMPTZ                                    |                                            |
| origen                   | ENUM('facial','manual')                         |                                            |
| estacion_id              | UUID FK → estaciones_biometricas.id             | Nullable si es manual                      |
| camara_estacion_id       | UUID FK → camaras_estacion.id                   | Nullable si es manual                      |
| evento_reconocimiento_id | UUID FK → eventos_reconocimiento.id            | Nullable si es manual                      |
| confianza_reconocimiento | NUMERIC(5,4)                                   | Nullable                                   |
| notificacion_enviada     | BOOLEAN DEFAULT false                          | Solo aplica a movimientos de alumnos       |
| registrado_por           | UUID FK → users.id                             | Nullable si el origen es facial             |
| cuenta_tecnica_id        | UUID FK → cuentas_tecnicas.id                  | Nullable si el origen es manual             |
| created_at               | TIMESTAMPTZ                                    |                                            |

Cada pase confirmado crea un movimiento. Los resúmenes diarios conservan el primer ingreso y la última salida, pero no
reemplazan ni eliminan salidas temporales, emergencias o reingresos.

En un dispositivo `bidireccional`, el primer reconocimiento confirmado del día se resuelve como ingreso y el siguiente
como salida; los siguientes alternan reingreso/salida. En dispositivos con modo fijo prevalece el modo configurado.
Si al cierre existe un ingreso sin salida, se crea una anomalía para revisión del Auxiliar. Si alguien sale sin
registrarse, el sistema no puede conocer la hora real: conserva la presencia abierta hasta que el Auxiliar registre una
salida manual estimada o indique que la hora es desconocida. Nunca se inventa automáticamente una hora de salida.

- Restricción `CHECK`: exactamente uno de `asistencia_alumno_id` o `asistencia_docente_id` debe estar informado, y
  exactamente uno de `registrado_por` o `cuenta_tecnica_id` identifica al actor.
- Un evento sincronizado tarde usa `ocurrido_en`/`capturado_en`, no la hora de recepción. Si ocurrió antes del cierre,
  reemplaza una falta automática por presente/tardanza y registra la corrección en auditoría.

### `anomalias_asistencia`

| Campo                 | Tipo                                                       | Notas |
|-----------------------|------------------------------------------------------------|-------|
| id                    | UUID PK                                                    |       |
| asistencia_alumno_id  | UUID FK → asistencias_alumnos.id                           | Nullable |
| asistencia_docente_id | UUID FK → asistencias_docentes.id                          | Nullable |
| tipo                  | ENUM('sin_salida','sin_ingreso','secuencia_invalida','otro') | |
| estado                | ENUM('pendiente','resuelta','descartada')                  |       |
| detalle               | TEXT                                                       |       |
| asignado_a            | UUID FK → users.id                                         | Auxiliar para alumnos; Yanina para docentes |
| resuelto_por          | UUID FK → users.id                                         | Nullable |
| resolucion            | TEXT                                                       | Nullable |
| created_at            | TIMESTAMPTZ                                                |       |
| resuelto_en           | TIMESTAMPTZ                                                | Nullable |

### `configuraciones_jornada`

| Campo                  | Tipo                    | Notas                                      |
|------------------------|-------------------------|--------------------------------------------|
| id                     | UUID PK                 |                                            |
| nombre                 | VARCHAR(150)            | Ej. Jornada regular secundaria             |
| grado_id               | UUID FK → grados.id     | Nullable = todos                           |
| dia_semana             | SMALLINT                | 1=Lun ... 7=Dom                            |
| hora_limite_puntual    | TIME                    | Ej. 07:45                                  |
| hora_cierre_asistencia | TIME                    | Fin de jornada; recién entonces genera falta |
| activo                 | BOOLEAN DEFAULT true    |                                            |
| configurado_por        | UUID FK → users.id      | Superadmin o Coordinador Académico         |
| created_at             | TIMESTAMPTZ             |                                            |
| updated_at             | TIMESTAMPTZ             |                                            |

Al cierre configurable, el sistema genera falta injustificada solamente para alumnos sin movimientos de ingreso. Un
alumno que llegó después de la hora puntual, pero antes del cierre, queda como tardanza y no como falta.

### `sesiones_clase`

| Campo               | Tipo                                                   | Notas |
|---------------------|--------------------------------------------------------|-------|
| id                  | UUID PK                                                |       |
| carga_academica_id  | UUID FK → carga_academica.id                           |       |
| fecha               | DATE                                                   |       |
| hora_inicio         | TIME                                                   |       |
| hora_fin            | TIME                                                   |       |
| estado              | ENUM('programada','realizada','cancelada','docente_ausente') | |
| motivo_cancelacion  | TEXT                                                   | Nullable |
| cancelada_por       | UUID FK → users.id                                     | Nullable; Coordinador Académico |
| docente_sustituto_id | UUID FK → docentes.id                                 | Nullable |
| revisado_planilla_por | UUID FK → users.id                                   | Nullable; Yanina |

El Coordinador Académico registra una cancelación por motivos académicos o institucionales. Una clase cancelada no
genera falta ni descuento. Si una clase sigue programada y no existe ingreso del docente al terminar `hora_fin`, el
sistema la marca `docente_ausente` para revisión de Yanina. La tardanza diaria se calcula contra la primera clase
programada del docente, incluso cuando esa sea su única clase.

### `tarifas_docentes`

| Campo          | Tipo                    | Notas                              |
|----------------|-------------------------|------------------------------------|
| id             | UUID PK                 |                                    |
| docente_id     | UUID FK → docentes.id   |                                    |
| tarifa_hora    | NUMERIC(10,2)           | Configurada por la cuenta autorizada de Yanina |
| vigente_desde  | DATE                    |                                    |
| vigente_hasta  | DATE                    | Nullable                           |
| registrado_por | UUID FK → users.id      | Yanina                             |
| created_at     | TIMESTAMPTZ             |                                    |

### `liquidaciones_descuento_docentes`

| Campo                         | Tipo                    | Notas                                  |
|-------------------------------|-------------------------|----------------------------------------|
| id                            | UUID PK                 |                                        |
| docente_id                    | UUID FK → docentes.id   |                                        |
| periodo_anio                  | SMALLINT                |                                        |
| periodo_mes                   | SMALLINT                |                                        |
| tarifa_hora_snapshot          | NUMERIC(10,2)           | Tarifa congelada para el cálculo       |
| minutos_tardanza              | INTEGER DEFAULT 0       |                                        |
| horas_falta_justificada       | NUMERIC(8,2) DEFAULT 0  |                                        |
| horas_falta_injustificada     | NUMERIC(8,2) DEFAULT 0  |                                        |
| monto_tardanza                | NUMERIC(10,2) DEFAULT 0 | minutos / 60 × tarifa                  |
| monto_falta_justificada       | NUMERIC(10,2) DEFAULT 0 | horas × tarifa                         |
| monto_falta_injustificada     | NUMERIC(10,2) DEFAULT 0 | horas × tarifa × 2                     |
| monto_ajuste                  | NUMERIC(10,2) DEFAULT 0 | Corrección manual justificada          |
| motivo_ajuste                 | TEXT                    | Nullable                               |
| monto_total_descuento         | NUMERIC(10,2)           | Total final                            |
| estado                         | ENUM('borrador','cerrada') | Cerrada es inmutable                |
| calculado_por                 | UUID FK → users.id      | Yanina                                 |
| cerrada_por                   | UUID FK → users.id      | Nullable; Yanina                       |
| cerrada_en                    | TIMESTAMPTZ             | Nullable                               |
| created_at                    | TIMESTAMPTZ             |                                        |
| updated_at                    | TIMESTAMPTZ             |                                        |
| UNIQUE                        | (docente_id, periodo_anio, periodo_mes) |                           |

Solo la cuenta específica de Yanina con los permisos `gestionar_planilla` y `cerrar_liquidacion` modifica tarifas,
ajustes y liquidaciones; no todos los usuarios con rol `administrativo`. Una tarifa nueva inicia una vigencia futura y
no recalcula clases, descuentos ni liquidaciones anteriores. Al cerrar una liquidación mensual queda inmutable; cualquier
corrección posterior se registra como ajuste compensatorio auditado.

---

## Reconocimiento Facial

Los perfiles biométricos pertenecen a `users` porque tanto alumnos como docentes pueden usar reconocimiento facial.
Los embeddings se guardan cifrados en PostgreSQL. Las fotos se almacenan en un bucket R2 privado y la base de datos
guarda únicamente su clave de objeto, nunca una URL pública.

### `consentimientos_biometricos`

| Campo             | Tipo                                                | Notas                                      |
|-------------------|-----------------------------------------------------|--------------------------------------------|
| id                | UUID PK                                             |                                            |
| user_id           | UUID FK → users.id                                  | Persona enrolada                           |
| estado            | ENUM('otorgado','revocado','pendiente')             |                                            |
| otorgado_por      | UUID FK → users.id                                  | Apoderado, docente o responsable autorizado |
| documento_version | VARCHAR(30)                                         | Versión del consentimiento aceptado        |
| otorgado_en       | TIMESTAMPTZ                                         | Nullable                                   |
| revocado_en       | TIMESTAMPTZ                                         | Nullable                                   |
| motivo_revocacion | TEXT                                                | Nullable                                   |
| created_at        | TIMESTAMPTZ                                         |                                            |
| updated_at        | TIMESTAMPTZ                                         |                                            |

### `perfiles_faciales`

| Campo              | Tipo                              | Notas                                                |
|--------------------|-----------------------------------|------------------------------------------------------|
| id                 | UUID PK                           |                                                      |
| user_id            | UUID FK → users.id UNIQUE          | Alumno o docente                                     |
| embedding_cifrado  | BYTEA                             | Representación matemática cifrada                    |
| modelo_version     | VARCHAR(100)                      | Modelo usado para generar el embedding               |
| calidad            | NUMERIC(5,4)                      | Calidad del enrolamiento                             |
| activo             | BOOLEAN DEFAULT true              | Se desactiva al revocar consentimiento               |
| enrolado_por       | UUID FK → users.id                 | Usuario que supervisó el enrolamiento                |
| enrolado_en        | TIMESTAMPTZ                       |                                                      |
| ultima_actualizacion_en | TIMESTAMPTZ                  |                                                      |
| created_at         | TIMESTAMPTZ                       |                                                      |
| updated_at         | TIMESTAMPTZ                       |                                                      |

### `archivos_biometricos`

| Campo           | Tipo                                      | Notas                                           |
|-----------------|-------------------------------------------|-------------------------------------------------|
| id              | UUID PK                                   |                                                 |
| user_id         | UUID FK → users.id                        | Nullable para evidencia no reconocida           |
| perfil_facial_id | UUID FK → perfiles_faciales.id            | Nullable                                        |
| tipo            | ENUM('enrolamiento','evidencia_excepcion') |                                                 |
| r2_object_key   | VARCHAR(500)                              | Clave privada; nunca URL pública                 |
| sha256          | VARCHAR(64)                               | Verificación de integridad                       |
| mime_type       | VARCHAR(100)                              | Solo formatos permitidos                         |
| expira_en       | TIMESTAMPTZ                               | Nullable para enrolamiento; obligatorio evidencia |
| eliminado_en    | TIMESTAMPTZ                               | Nullable                                        |
| created_at      | TIMESTAMPTZ                               |                                                 |

### `estaciones_biometricas`

| Campo           | Tipo                        | Notas                                           |
|-----------------|-----------------------------|-------------------------------------------------|
| id              | UUID PK                     |                                                 |
| codigo          | VARCHAR(100) UNIQUE         | Ej. `puerta-principal`                          |
| nombre          | VARCHAR(150)                |                                                 |
| ubicacion       | VARCHAR(200)                |                                                 |
| tipo_equipo     | ENUM('pc','celular','tablet','otro')      |                                |
| cuenta_tecnica_id | UUID FK → cuentas_tecnicas.id UNIQUE | Credencial vinculada a un dispositivo |
| activo          | BOOLEAN DEFAULT true        |                                                 |
| ultimo_contacto | TIMESTAMPTZ                 | Nullable                                        |
| configuracion   | JSONB                       | Umbrales y capacidades no sensibles             |
| activado_en     | TIMESTAMPTZ                 | Nullable                                        |
| revocado_en     | TIMESTAMPTZ                 | Nullable                                        |
| created_at      | TIMESTAMPTZ                 |                                                 |
| updated_at      | TIMESTAMPTZ                 |                                                 |

### `camaras_estacion`

| Campo       | Tipo                                      | Notas |
|-------------|-------------------------------------------|-------|
| id          | UUID PK                                   |       |
| estacion_id | UUID FK → estaciones_biometricas.id       |       |
| device_id_navegador | VARCHAR(255)                     | Identificador reportado por `getUserMedia()` |
| nombre      | VARCHAR(150)                              | Ej. Cámara entrada izquierda |
| ubicacion   | VARCHAR(200)                              |       |
| modo        | ENUM('entrada','salida','bidireccional')  | Ayuda a resolver el movimiento |
| activo      | BOOLEAN DEFAULT true                      |       |

Una estación representa un navegador activado en una PC, celular o tablet. Puede tener una o varias cámaras; la sesión
técnica pertenece a la estación y cada evento identifica qué cámara lo originó.

### `cuentas_tecnicas`

| Campo           | Tipo                                      | Notas |
|-----------------|-------------------------------------------|-------|
| id              | UUID PK                                   |       |
| nombre          | VARCHAR(150)                              | Identidad visible en administración |
| tipo            | ENUM('estacion_web','servicio_facial')      |    |
| token_hash      | VARCHAR(255)                              | Nunca guardar token en texto plano |
| scopes          | JSONB                                     | Capacidades técnicas mínimas |
| activo          | BOOLEAN DEFAULT true                      | Se desactiva, no se elimina |
| creado_por      | UUID FK → users.id                        | Cuenta con `gestionar_dispositivos` |
| ultimo_contacto | TIMESTAMPTZ                               | Nullable |
| token_rotado_en | TIMESTAMPTZ                               | Nullable |
| created_at      | TIMESTAMPTZ                               |       |
| updated_at      | TIMESTAMPTZ                               |       |

Las cuentas técnicas se crean desde administración, pero no son filas de `users`: no tienen correo falso, contraseña ni
acceso interactivo. Cada cuenta de tipo estación se vincula a un solo navegador activado. Se pueden desactivar y rotar sus
credenciales, pero no eliminar su historial.

### `activaciones_estacion`

| Campo             | Tipo                              | Notas |
|-------------------|-----------------------------------|-------|
| id                | UUID PK                           |       |
| estacion_id       | UUID FK → estaciones_biometricas.id    |   |
| codigo_hash       | VARCHAR(255)                      | Nunca guardar el código en texto plano |
| expira_en         | TIMESTAMPTZ                       | Máximo 10 minutos |
| usado_en          | TIMESTAMPTZ                       | Nullable; uso único |
| creado_por        | UUID FK → users.id                | Cuenta con `gestionar_dispositivos` |
| created_at        | TIMESTAMPTZ                       |       |

La activación crea una sesión técnica en cookie `httpOnly` sin copiar la sesión personal del responsable. Conocer la URL
no permite registrar asistencia: cada evento requiere una cuenta técnica activa, scopes válidos y una estación no
revocada.

### `eventos_reconocimiento`

| Campo                 | Tipo                                                        | Notas                                  |
|-----------------------|-------------------------------------------------------------|----------------------------------------|
| id                    | UUID PK                                                     |                                        |
| idempotency_key       | VARCHAR(191) UNIQUE                                         | Evita registros duplicados             |
| estacion_id           | UUID FK → estaciones_biometricas.id                         |                                        |
| camara_estacion_id    | UUID FK → camaras_estacion.id                               | Cámara que originó la captura          |
| cuenta_tecnica_id     | UUID FK → cuentas_tecnicas.id                               | Actor autenticado                       |
| user_id               | UUID FK → users.id                                          | Nullable si no fue reconocido          |
| tipo_persona          | ENUM('alumno','docente','desconocido')                      |                                        |
| tipo_evento_resuelto  | ENUM('ingreso','salida','reingreso')                         | Nullable; decidido por Laravel          |
| confianza             | NUMERIC(5,4)                                                | 0.0000 a 1.0000                        |
| prueba_vida_superada  | BOOLEAN                                                     |                                        |
| estado                | ENUM('aceptado','pendiente_revision','rechazado','duplicado') |                                      |
| motivo_estado         | VARCHAR(255)                                                | Nullable                               |
| evidencia_archivo_id  | UUID FK → archivos_biometricos.id                           | Nullable; solo excepciones             |
| capturado_en          | TIMESTAMPTZ                                                 | Hora reportada por el dispositivo      |
| recibido_en           | TIMESTAMPTZ                                                 | Hora recibida por Laravel              |
| revisado_por          | UUID FK → users.id                                          | Nullable; Auxiliar o Yanina            |
| revisado_en           | TIMESTAMPTZ                                                 | Nullable                               |
| created_at            | TIMESTAMPTZ                                                 |                                        |
| updated_at            | TIMESTAMPTZ                                                 |                                        |

### Índices y Restricciones Biométricas

- Índice parcial en `eventos_reconocimiento(estado, capturado_en)` para pendientes de revisión.
- Índice en `eventos_reconocimiento(user_id, capturado_en)` para historial y detección de duplicados.
- Índice único parcial en `consentimientos_biometricos(user_id)` cuando `estado = 'otorgado'`, conservando el historial.
- Restricción `CHECK` para que `confianza` y `calidad` estén entre 0 y 1.
- `evidencia_archivo_id` solo se completa para excepciones justificadas; los eventos rutinarios no guardan capturas.
- Al revocar consentimiento se desactiva el perfil y se programa la eliminación de embeddings y objetos R2.
- Las asistencias automáticas usan `cuenta_tecnica_id`; las correcciones conservan al usuario humano responsable.
- Cada estación funciona como una cuenta técnica no interactiva: no posee correo ni contraseña, no inicia sesión en
  paneles humanos y solo puede usar sus `scopes`. Sus credenciales se rotan o revocan, pero el registro no se elimina.
- Valores iniciales para piloto: aceptación automática con confianza `>= 0.85` y prueba de vida superada; revisión entre
  `0.65` y `0.8499`; rechazo por debajo de `0.65` o sin prueba de vida. Los umbrales se calibran con pruebas reales,
  porque la escala depende del modelo.
- El enrolamiento usa entre 3 y 5 fotos de buena calidad (frontal y ángulos leves), un único perfil activo por persona y
  re-enrolamiento controlado cuando cambia `modelo_version`.
- Objetivo de respuesta: 2 segundos en operación normal, timeout de 5 segundos y alternativa manual. La ventana inicial
  para descartar duplicados es 30 segundos, configurable por dispositivo.

---

## Pagos y Finanzas

### `configuraciones_financieras`

| Campo                         | Tipo                          | Notas |
|-------------------------------|-------------------------------|-------|
| id                            | UUID PK                       |       |
| periodo_academico_id          | UUID FK → periodos_academicos.id |    |
| dia_generacion_mensualidad    | SMALLINT                      |       |
| dia_vencimiento_mensualidad   | SMALLINT                      |       |
| configurado_por               | UUID FK → users.id            | Cuenta específica autorizada |
| vigente_desde                 | DATE                          |       |
| vigente_hasta                 | DATE                          | Nullable |

Solo la cuenta específica con permiso `gestionar_finanzas`, actualmente Yanina, modifica montos, fechas, beneficios y
reglas de generación. Los valores quedan versionados por vigencia; nunca se sobrescribe una configuración histórica.

### `conceptos_pago`

| Campo                  | Tipo                                               | Notas                                               |
|------------------------|----------------------------------------------------|-----------------------------------------------------|
| id                     | UUID PK                                            |                                                     |
| nombre                 | VARCHAR(200)                                       | Matrícula 2026, Mensualidad Abril, Cuota de Ingreso |
| tipo                   | ENUM('cuota_ingreso','matricula','mensualidad','otro') |                                                  |
| periodo_academico_id   | UUID FK → periodos_academicos.id                       |                                                  |
| periodo_anio           | SMALLINT                                           |                                                     |
| periodo_mes            | SMALLINT                                           | Nullable. Obligatorio para mensualidad              |
| monto_base             | NUMERIC(8,2)                                       | Configurable por Yanina                             |
| descuento_pronto_pago  | NUMERIC(8,2) DEFAULT 0.00                          | Ej. 30.00 para mensualidad                          |
| fecha_limite_pronto_pago | DATE                                             | Nullable                                            |
| estado                 | ENUM('borrador','vigente','cerrado')               | Cerrado impide generar nuevas deudas                |
| bloqueado_en           | TIMESTAMPTZ                                        | Nullable. Se completa al generar la primera deuda   |
| creado_por             | UUID FK → users.id                                 | Yanina o superadmin                                 |
| created_at             | TIMESTAMPTZ                                        |                                                     |
| updated_at             | TIMESTAMPTZ                                        |                                                     |

Un concepto puede modificarse únicamente mientras esté en `borrador` y `bloqueado_en` sea nulo. `vigente` permite
generar deudas y `cerrado` impide generar nuevas. Al generar la primera deuda, el concepto queda bloqueado contra
ediciones aunque continúe vigente; modificar el catálogo después no cambia automáticamente obligaciones previamente
emitidas.

El bloqueo del concepto protege su definición histórica, pero la cuenta específica con `gestionar_finanzas` puede
realizar ajustes controlados sobre obligaciones individuales o grupos de obligaciones todavía pendientes.

### `beneficios_alumnos`

| Campo                    | Tipo                                                | Notas                                      |
|--------------------------|-----------------------------------------------------|--------------------------------------------|
| id                       | UUID PK                                             |                                            |
| alumno_id                | UUID FK → alumnos.id                                |                                            |
| tipo                     | ENUM('beca','descuento')                             |                                            |
| modalidad                | ENUM('porcentaje','monto_fijo','exoneracion')        |                                            |
| valor                    | NUMERIC(8,2)                                        | Nullable solo para exoneración             |
| aplica_mensualidad       | BOOLEAN DEFAULT true                                | Aplicación por defecto                      |
| aplica_matricula         | BOOLEAN DEFAULT false                               | Debe marcarse explícitamente                |
| aplica_cuota_ingreso     | BOOLEAN DEFAULT false                               | Debe marcarse explícitamente                |
| acumulable_pronto_pago   | BOOLEAN DEFAULT false                               |                                            |
| vigente_desde            | DATE                                                |                                            |
| vigente_hasta            | DATE                                                | Nullable                                   |
| motivo                   | TEXT                                                | Ej. primer puesto o acuerdo especial        |
| activo                   | BOOLEAN DEFAULT true                                | Desactivar solo afecta deudas futuras       |
| registrado_por           | UUID FK → users.id                                  | Yanina                                     |
| created_at               | TIMESTAMPTZ                                         |                                            |
| updated_at               | TIMESTAMPTZ                                         |                                            |

Para `porcentaje`, `valor` debe estar entre 0 y 100. Para `monto_fijo`, debe ser mayor que cero. En `exoneracion`, el
valor puede ser nulo porque el descuento equivale al total aplicable. Cada deuda referencia como máximo un beneficio;
si existen beneficios coincidentes, Yanina selecciona el aplicable antes de generarla.

### `obligaciones_pago`

| Campo                          | Tipo                                 | Notas                                      |
|--------------------------------|--------------------------------------|--------------------------------------------|
| id                             | UUID PK                              |                                            |
| alumno_id                      | UUID FK → alumnos.id                 |                                            |
| concepto_id                    | UUID FK → conceptos_pago.id          |                                            |
| monto_base_snapshot            | NUMERIC(8,2)                         | Copia inmutable del monto del concepto     |
| beneficio_id                   | UUID FK → beneficios_alumnos.id      | Nullable                                   |
| monto_beneficio_snapshot       | NUMERIC(8,2) DEFAULT 0.00            | Copia del beneficio aplicado               |
| descuento_pronto_pago_aplicado | NUMERIC(8,2) DEFAULT 0.00            | Monto aplicado, no solo indicador          |
| monto_ordinario_snapshot       | NUMERIC(8,2)                         | Total congelado después de beneficios      |
| monto_pronto_pago_snapshot     | NUMERIC(8,2)                         | Total si cancela dentro del plazo          |
| fecha_limite_pronto_pago_snapshot | DATE                              | Plazo congelado para esta obligación       |
| monto_cobrado                  | NUMERIC(8,2)                         | Nullable; S/450 a tiempo o S/480 después   |
| fecha_vencimiento              | DATE                                 |                                            |
| fecha_pago                     | TIMESTAMPTZ                          | Nullable                                   |
| estado                         | ENUM('pendiente','pagado','vencido') |                                            |
| registrado_por                 | UUID FK → users.id                   | Yanina                                     |
| actualizado_finanzas_por       | UUID FK → users.id                   | Nullable; cuenta con `gestionar_finanzas`  |
| motivo_ultima_modificacion     | TEXT                                | Nullable; obligatorio al ajustar una deuda |
| created_at                     | TIMESTAMPTZ                          |                                            |
| updated_at                     | TIMESTAMPTZ                          |                                            |

La deuda se emite conservando dos valores: monto ordinario y monto de pronto pago. No existen pagos parciales. Al
registrar el pago completo, el sistema compara `fecha_pago` con `fecha_limite_pronto_pago_snapshot`: cobra el monto de
pronto pago si fue pagado dentro del plazo y el monto ordinario si fue pagado después. El descuento no se acumula con un
beneficio salvo que `acumulable_pronto_pago = true`.

Mientras la deuda está pendiente y el plazo sigue vigente, el portal muestra el monto de pronto pago junto con su fecha
límite y también el monto ordinario posterior. Al vencer el plazo, el monto exigible mostrado pasa al ordinario.

La cuenta específica con `gestionar_finanzas` puede modificar en una deuda todavía `pendiente`:

- monto ordinario;
- monto de pronto pago o descuento;
- fecha límite de pronto pago;
- fecha de vencimiento;
- beneficio aplicado.

Puede aplicar el cambio a una sola deuda, un grado/sección o todas las deudas pendientes de un concepto. Cada ajuste
exige motivo, registra valores anteriores/nuevos en auditoría y notifica a padres/alumnos afectados. Si se amplía una
fecha límite vencida antes de registrar el pago, la deuda vuelve a ser elegible para pronto pago. Las deudas `pagado` o
anuladas y sus movimientos nunca se modifican; cualquier corrección usa anulación/devolución y un nuevo registro.

Cada fila de `obligaciones_pago` representa una obligación o deuda emitida. El dinero recibido y sus correcciones se
registran como movimientos inmutables en `movimientos_pago`.

### `movimientos_pago`

| Campo           | Tipo                                      | Notas                                  |
|-----------------|-------------------------------------------|----------------------------------------|
| id              | UUID PK                                   |                                        |
| obligacion_pago_id | UUID FK → obligaciones_pago.id                        | Obligación afectada                    |
| tipo            | ENUM('pago','anulacion','devolucion')        |                                        |
| monto           | NUMERIC(8,2)                              | Siempre positivo                       |
| medio_pago      | ENUM('efectivo','transferencia','yape','plin','otro') | Obligatorio para pago        |
| referencia      | VARCHAR(150)                              | Obligatoria excepto efectivo; única por medio/proveedor |
| numero_recibo   | VARCHAR(50)                               | Secuencial e inmutable                   |
| comprobante_ruta | VARCHAR(500)                             | Nullable. Archivo privado protegido      |
| motivo          | TEXT                                      | Obligatorio para anulación/devolución  |
| registrado_por  | UUID FK → users.id                        | Yanina                                  |
| created_at      | TIMESTAMPTZ                               | Inmutable                               |

Cada obligación admite un único movimiento de tipo `pago` activo y debe coincidir exactamente con el monto aplicable por
fecha. Los movimientos no se eliminan ni modifican físicamente. En la versión actual no existe pasarela: Yanina verifica
el pago fuera del sistema y registra manualmente el medio, referencia y comprobante.

- Una devolución se registra como movimiento inmutable; nunca se borra el pago original.
- Un pago aplicado a la deuda equivocada se corrige con anulación y un nuevo pago completo.
- Las referencias de Yape, Plin y transferencias no pueden repetirse para el mismo proveedor/medio.

---

## Incidencias y Psicología

### `incidencias` (Cuaderno Virtual)

| Campo               | Tipo                                                     | Notas          |
|---------------------|----------------------------------------------------------|----------------|
| id                  | UUID PK                                                  |                |
| alumno_id           | UUID FK → alumnos.id                                     |                |
| reportado_por       | UUID FK → users.id                                       | Auxiliar o TOE |
| fecha               | TIMESTAMPTZ                                              |                |
| tipo                | ENUM('conducta','tardanza_constante','academico','otro') |                |
| severidad           | ENUM('leve','moderada','grave')                          |                |
| descripcion         | TEXT                                                     |                |
| asignado_a          | ENUM('auxiliar','toe','psicologia')                      |                |
| estado              | ENUM('abierto','derivado_toe','derivado_psicologia','notificado_padre','resuelto') | |
| created_at          | TIMESTAMPTZ                                              |                |
| updated_at          | TIMESTAMPTZ                                              |                |

### `atenciones_psicologia` (Registro Privado)

| Campo          | Tipo                     | Notas                                     |
|----------------|--------------------------|-------------------------------------------|
| id             | UUID PK                  |                                           |
| incidencia_id  | UUID FK → incidencias.id | Nullable                                  |
| alumno_id      | UUID FK → alumnos.id     |                                           |
| psicologa_id   | UUID FK → users.id       |                                           |
| fecha_atencion | TIMESTAMPTZ              |                                           |
| notas_privadas | TEXT                     | Confidencial. Solo Psicología y superadmin (Promotor) |
| created_at     | TIMESTAMPTZ              |                                           |
| updated_at     | TIMESTAMPTZ              |                                           |

### `historial_incidencias`

| Campo         | Tipo                       | Notas |
|---------------|----------------------------|-------|
| id            | UUID PK                    |       |
| incidencia_id | UUID FK → incidencias.id   |       |
| accion        | VARCHAR(100)               | Creación, derivación, comentario, resolución |
| detalle       | TEXT                       |       |
| archivo_ruta  | VARCHAR(500)               | Nullable; privado |
| registrado_por | UUID FK → users.id        |       |
| created_at    | TIMESTAMPTZ                |       |

El Auxiliar registra y deriva a TOE; TOE también puede registrar directamente, completar el historial, notificar,
resolver o derivar a Psicología.

---

## Exámenes y Notas

### `examenes`

| Campo            | Tipo                                | Notas                    |
|------------------|-------------------------------------|--------------------------|
| id               | UUID PK                             |                          |
| carga_academica_id | UUID FK → carga_academica.id        | Define curso, sección y docente |
| titulo           | VARCHAR(200)                        | Semanal 1 - I Bimestre   |
| fecha_aplicacion | DATE                                | Siempre viernes          |
| periodo_nombre   | VARCHAR(50)                         | Ej. I Bimestre           |
| canal            | ENUM('general','ciencias','letras') | 5° se divide por canales |
| total_preguntas  | INTEGER                             | 40 (1°-4°) o 60 (5°)     |
| puntaje_maximo   | NUMERIC(6,2)                       | Límite para validar resultados |
| estado           | ENUM('borrador','listo','publicado','cerrado') | |
| publicado_por    | UUID FK → users.id                  | Nullable                 |
| publicado_en     | TIMESTAMPTZ                         | Nullable                 |
| created_at       | TIMESTAMPTZ                         |                          |
| updated_at       | TIMESTAMPTZ                         |                          |

### `notas`

| Campo          | Tipo                   | Notas                           |
|----------------|------------------------|---------------------------------|
| id             | UUID PK                |                                 |
| examen_id      | UUID FK → examenes.id  |                                 |
| matricula_id   | UUID FK → matriculas.id | Garantiza pertenencia al periodo/sección |
| puntaje        | NUMERIC(6,2)           | Nullable para ausente/exonerado |
| estado         | ENUM('registrada','ausente','exonerado','pendiente') | |
| observacion    | TEXT                   | Nullable |
| puesto_ranking | INTEGER                | Nullable. Calculado al publicar |
| registrado_por | UUID FK → users.id     |                                 |
| created_at     | TIMESTAMPTZ            |                                 |
| updated_at     | TIMESTAMPTZ            |                                 |
| UNIQUE         | (examen_id, matricula_id) |                               |

El sistema no administra exámenes ni corrige respuestas. Los exámenes son físicos y el sistema almacena resultados ya
procesados. El docente solo registra o corrige notas de sus cargas académicas activas; el Coordinador Académico puede
revisar y publicar. Al publicar se genera una notificación en el panel y por correo. El alumno ve solo sus resultados y
el padre solo los de alumnos vinculados. El ranking usa alcance de examen/sección/canal y empate con posición compartida.

El puntaje debe estar entre cero y `puntaje_maximo`. Solo matrículas activas participan; ausentes, exonerados y pendientes
no reciben puesto. La carga masiva primero valida y muestra una previsualización, luego guarda todo en una transacción.
Corregir un resultado publicado conserva auditoría, recalcula el ranking y genera una nueva notificación. Un examen
`cerrado` no admite cambios salvo reapertura auditada por el Coordinador Académico o superadmin.

### `reportes_academicos`

| Campo                | Tipo                              | Notas |
|----------------------|-----------------------------------|-------|
| id                   | UUID PK                           |       |
| matricula_id         | UUID FK → matriculas.id           |       |
| periodo_nombre       | VARCHAR(50)                       | Bimestre, ciclo u otro |
| tipo                 | ENUM('libreta','reporte_academia') | Según periodo académico |
| archivo_ruta         | VARCHAR(500)                      | Archivo privado |
| publicado_en         | TIMESTAMPTZ                       |       |
| generado_por         | UUID FK → users.id                |       |

---

## Tablas Adaptadas (Materiales, Horarios, Comunicados)

### `materiales`

| Campo          | Tipo                 | Notas                                       |
|----------------|----------------------|---------------------------------------------|
| id             | UUID PK              |                                             |
| titulo         | VARCHAR(200)         |                                             |
| descripcion    | TEXT                 | Nullable                                    |
| tipo           | VARCHAR(20)          | pdf / video / enlace / otro                 |
| ruta_o_url     | VARCHAR(500)         | Ruta local si es archivo, URL si es externo |
| carga_academica_id | UUID FK → carga_academica.id | Nullable para material general       |
| subido_por     | UUID FK → users.id   | Coordinador o Docente                       |
| semana         | SMALLINT             | Nullable                                    |
| activo         | BOOLEAN DEFAULT true |                                             |
| created_at     | TIMESTAMPTZ          |                                             |
| updated_at     | TIMESTAMPTZ          |                                             |

### `horarios`

| Campo       | Tipo                  | Notas         |
|-------------|-----------------------|---------------|
| id          | UUID PK               |               |
| carga_academica_id | UUID FK → carga_academica.id | |
| dia_semana  | SMALLINT              | 1=Lun … 7=Dom |
| hora_inicio | TIME                  |               |
| hora_fin    | TIME                  |               |
| aula        | VARCHAR(50)           | Nullable      |
| created_at  | TIMESTAMPTZ           |               |
| updated_at  | TIMESTAMPTZ           |               |

### `eventos_calendario`

| Campo                | Tipo                                | Notas |
|----------------------|-------------------------------------|-------|
| id                   | UUID PK                             |       |
| periodo_academico_id | UUID FK → periodos_academicos.id    |       |
| tipo                 | ENUM('evento','examen','simulacro','no_laboral') | |
| titulo               | VARCHAR(200)                        |       |
| fecha_inicio         | TIMESTAMPTZ                         |       |
| fecha_fin            | TIMESTAMPTZ                         |       |
| seccion_id           | UUID FK → secciones.id              | Nullable = general |
| creado_por           | UUID FK → users.id                  | Coordinador Académico |

### `comunicados`

| Campo             | Tipo                  | Notas                                               |
|-------------------|-----------------------|-----------------------------------------------------|
| id                | UUID PK               |                                                     |
| titulo            | VARCHAR(200)          |                                                     |
| contenido         | TEXT                  |                                                     |
| publicado_por     | UUID FK → users.id    |                                                     |
| destinatarios     | JSONB                 | `{"roles": ["padre","docente"], "grados": [1,2,3]}` |
| importante        | BOOLEAN DEFAULT false | Dispara notificación por correo                     |
| fecha_publicacion | TIMESTAMPTZ           |                                                     |
| created_at        | TIMESTAMPTZ           |                                                     |
| updated_at        | TIMESTAMPTZ           |                                                     |

### `comunicado_lecturas`

| Campo         | Tipo                       | Notas |
|---------------|----------------------------|-------|
| comunicado_id | UUID FK → comunicados.id   |       |
| user_id       | UUID FK → users.id         |       |
| leido_en      | TIMESTAMPTZ                |       |
| archivado_en  | TIMESTAMPTZ                | Nullable |
| PK            | (comunicado_id, user_id)   |       |

### `notificaciones`

| Campo          | Tipo                                      | Notas |
|----------------|-------------------------------------------|-------|
| id             | UUID PK                                   |       |
| user_id        | UUID FK → users.id                        | Destinatario |
| tipo           | VARCHAR(100)                              | Nota publicada, asistencia, pago, comunicado |
| titulo         | VARCHAR(200)                              |       |
| contenido      | TEXT                                      |       |
| datos          | JSONB                                     | IDs y ruta interna, sin datos privados innecesarios |
| canal          | ENUM('panel','correo')                    | V1 |
| estado         | ENUM('pendiente','enviada','fallida','leida') | |
| enviada_en     | TIMESTAMPTZ                               | Nullable |
| leida_en       | TIMESTAMPTZ                               | Nullable |

---

### `audit_logs`

| Campo      | Tipo               | Notas                                 |
|------------|--------------------|---------------------------------------|
| id         | BIGSERIAL PK       | Incremental, no UUID (volumen alto)   |
| user_id    | UUID FK → users.id | Nullable                              |
| action     | VARCHAR(100)       | Ej: nota.actualizada, pago.registrado |
| model      | VARCHAR(100)       | Ej: Nota                              |
| model_id   | VARCHAR(36)        | UUID del registro afectado            |
| old_values | JSONB              | Nullable                              |
| new_values | JSONB              | Nullable                              |
| ip         | VARCHAR(45)        |                                       |
| created_at | TIMESTAMPTZ        |                                       |

---

## Configuración en Laravel (.env)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cienciasnet
DB_USERNAME=cienciasnet_user
DB_PASSWORD=tu_password_seguro

R2_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
R2_BUCKET_BIOMETRICS=cienciasnet-biometria
R2_ACCESS_KEY_ID=credencial_privada
R2_SECRET_ACCESS_KEY=secreto_privado
BIOMETRIC_ENCRYPTION_KEY=clave_independiente_de_app_key
```

---

## Ventajas de PostgreSQL para este proyecto

| Característica        | Uso en CienciasNET                                     |
|-----------------------|--------------------------------------------------------|
| **JSONB**             | Columna `destinatarios` en comunicados y `audit_logs`  |
| **UUID nativo**       | PKs de todas las tablas sin autoincrement              |
| **TIMESTAMPTZ**       | Fechas con zona horaria UTC-5 Lima                     |
| **NUMERIC(x,y)**      | Notas y montos sin errores de punto flotante           |
| **Full-text search**  | Búsqueda de alumnos por nombre sin extensiones         |
| **Índices parciales** | Pagos pendientes, incidencias abiertas                 |
| **ENUM nativo**       | Estados normalizados (asistencias, pagos, incidencias) |
