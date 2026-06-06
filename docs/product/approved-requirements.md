# CienciasNET — Acta de Reunión de Definición de Funcionalidades

> **Registro detallado de requerimientos acordados.** Las decisiones posteriores documentadas en ADRs o dominios
> especializados prevalecen cuando exista una diferencia.

**Proyecto:** Sistema Intranet Administrativo y Académico — Colegio Ciencias
**Repositorio:** `iovargasjeff/CienciasNET`
**Modelo de entrega:** Pago único + mantenimiento mensual
**Fecha de reunión:** ✅ Realizada

---

## Resultado de la Reunión

La reunión de levantamiento de requerimientos con el equipo directivo del Colegio Ciencias fue completada exitosamente.
A continuación se documentan las decisiones tomadas y los requerimientos específicos aprobados.

---

## Jerarquía y Roles Aprobados

| Rol en Sistema          | Actor Físico          | Funciones                                                                                                                    |
|-------------------------|-----------------------|------------------------------------------------------------------------------------------------------------------------------|
| `superadmin`            | Promotor              | Acceso total e irrestricto a todos los módulos, reportes y configuraciones                                                   |
| `gestor_usuarios`       | Persona delegada      | Gestión diaria de cuentas, perfiles, roles operativos y vínculos padre-alumno; no puede asignar `superadmin`                  |
| `toe`                   | Dpto. TOE             | Coordinación de docentes, recepción de reportes de Auxiliar, escalar a Psicología, notificación a padres sobre faltas graves |
| `psicologia`            | Psicóloga(o)          | Acceso a casos derivados, registro de atenciones confidenciales, soporte emocional                                           |
| `auxiliar`              | Auxiliar de Educación | Supervisión en puerta, gestión de excepciones de asistencia (reconocimiento facial), Cuaderno de Incidencias virtual |
| `coordinador_academico` | Coord. Académico      | Periodos, grados, secciones, cursos, carga docente, evaluaciones y cancelación académica de clases                            |
| `administrativo`        | Yanina (Contabilidad) | Cuenta específica autorizada para finanzas, pagos, asistencia docente y cierre de planilla                                   |
| `docente`               | Profesores            | Horario, asistencia facial y registro de notas solo de cursos/secciones asignados                                            |
| `padre`                 | Padres / Apoderados   | Portal para ver notas, asistencias, incidencias y estado de cuenta de sus hijos                                              |
| `alumno`                | Estudiantes           | Consulta de su propia asistencia, notas, estado de cuenta, materiales, horarios y comunicados                                |

---

## Módulos Aprobados

| # | Módulo                                      | Responsable                   | V1.0 |
|---|---------------------------------------------|-------------------------------|------|
| 1 | Control de Asistencia (Alumnos)             | Auxiliar                      | ✅    |
| 2 | Control de Asistencia y Planilla (Docentes) | Administrativo (Yanina)       | ✅    |
| 3 | Gestión Financiera y Pagos                  | Administrativo (Yanina)       | ✅    |
| 4 | Evaluación y Academia                       | Coordinador Académico         | ✅    |
| 5 | Cuaderno de Incidencias y Psicología        | Auxiliar → TOE → Psicología   | ✅    |
| 6 | Materiales de Estudio                       | Coordinador / Docente         | ✅    |
| 7 | Horarios y Calendario                       | Coordinador                   | ✅    |
| 8 | Comunicados                                 | Superadmin / TOE / Coordinador | ✅    |

---

## Reglas de Negocio Clave Aprobadas

### Asistencia Alumnos

- Registro facial en entradas/salidas autorizadas; admite múltiples cámaras, celulares o tablets y no se toma por salón
- Auxiliar supervisa y resuelve reconocimientos dudosos o no reconocidos
- Horario límite: 7:45 AM
- Correo automático al padre por ingreso, salida y salida de emergencia
- Se permiten salidas temporales/emergencias y reingresos durante el mismo día
- El primer pase bidireccional del día es ingreso y el siguiente salida; una presencia sin salida crea anomalía para el Auxiliar
- Si alguien sale sin registrarse, el Auxiliar documenta la corrección; el sistema nunca inventa una hora
- La falta se genera al cierre configurable de la jornada completa, solo si no existió ningún ingreso
- Coordinador Académico o Superadmin configura puntualidad y cierre por grado/día
- Ingresos sincronizados después, pero capturados antes del cierre, corrigen automáticamente la falta
- Solo faltas injustificadas cuentan para la alerta de 3 faltas a TOE y Auxiliar
- Solo TOE o Auxiliar pueden justificar faltas

### Asistencia Docentes (Planilla)

- Registro facial automático en entradas/salidas autorizadas
- Yanina supervisa y corrige excepciones; los docentes no editan su propia asistencia
- Minutos de tardanza acumulables mensualmente
- Yanina configura tarifa por hora vigente para calcular descuentos monetarios
- La tardanza se calcula contra la primera clase programada del día, incluso si es la única
- El Coordinador Académico marca clases canceladas; una clase programada sin asistencia genera falta al terminar
- Las tarifas afectan solo periodos futuros y Yanina cierra la liquidación mensual
- Tardanza: minutos / 60 × tarifa por hora
- Falta justificada: horas no laboradas × tarifa por hora
- Falta injustificada: horas no laboradas × tarifa por hora × 2
- Registro de docente sustituto

### Integración Facial Aprobada

- Servicio facial Python independiente desplegado en el VPS
- Cada PC, celular o tablet se activa como estación web mediante QR/código temporal, sin usar la cuenta personal del responsable
- Una PC puede operar una o varias cámaras; celulares y tablets normalmente utilizan una cámara a la vez
- Las estaciones web autorizadas envían capturas puntuales por HTTPS
- Laravel conserva todas las reglas de negocio y es el único componente que registra asistencias
- Cloudflare R2 privado almacena fotos de enrolamiento y evidencia excepcional con retención limitada
- Las capturas rutinarias se procesan en memoria y no se conservan
- El enrolamiento requiere consentimiento registrado y existe un método manual alternativo
- El consentimiento se valida al enrolar; en cada pase se valida el dispositivo y se reconoce solo contra perfiles activos
- Criterios iniciales: prueba de vida obligatoria, aceptación desde 0.85, revisión desde 0.65, timeout de 5 segundos y alternativa manual

### Finanzas

- Cuota de Ingreso: S/ 200.00, Matrícula: S/ 480.00, Mensualidad Base: S/ 480.00
- Los montos son valores base iniciales configurables por periodo, no importes fijos permanentes
- Yanina administra directamente conceptos, becas y descuentos; no existe aprobación de Dirección dentro de la app
- Solo la cuenta administrativa específica de Yanina puede modificar montos, vencimientos y fechas límite
- Descuento por pronto pago: S/ 30.00 (si paga hasta fin de mes)
- Becas y descuentos definen modalidad, valor, conceptos aplicables, vigencia y si acumulan pronto pago
- Los beneficios aplican por defecto a mensualidades; matrícula y cuota de ingreso deben marcarse explícitamente
- Cada deuda aplica como máximo un beneficio; Yanina resuelve cualquier coincidencia antes de generarla
- No existen pagos parciales: cada deuda se paga en una sola operación
- Si paga hasta la fecha límite corresponde S/ 450.00; después corresponde el monto ordinario de S/ 480.00
- Los cambios generales afectan deudas futuras; Yanina puede aplicar ajustes auditados a deudas todavía pendientes
- Las deudas pagadas/anuladas y los pagos históricos nunca se modifican
- Cada deuda congela monto ordinario, monto de pronto pago y fecha límite
- Devoluciones y correcciones se manejan mediante movimientos inmutables y auditados
- Los pagos se verifican fuera del sistema y Yanina los registra manualmente como efectivo, transferencia, Yape, Plin u otro
- No existe pasarela de pagos en la versión actual

### Gestión de Cuentas

- El Promotor conserva `superadmin`
- `superadmin` puede delegar la gestión diaria a una cuenta específica con rol `gestor_usuarios`
- El permiso no se entrega automáticamente a todas las cuentas `administrativo`
- `gestor_usuarios` puede asignar roles operativos, pero nunca `superadmin` ni sus propios permisos
- No existe autorregistro; `gestor_usuarios` o `superadmin` vinculan padres/apoderados con alumnos
- Cada padre usa correo único; un padre puede vincularse a varios alumnos y un alumno a varios padres
- Una persona que también sea trabajador usa una sola cuenta con varios roles y contextos de portal separados
- Las estaciones web usan sesiones técnicas sin correo ni acceso al panel; cada estación puede revocarse independientemente

### Evaluación

- Exámenes viernes, publicación martes
- 1°-4°: 40 preguntas. 5°: 60 preguntas (ciencias/letras)
- El sistema no toma ni corrige exámenes; almacena resultados de pruebas físicas ya procesadas
- Periodos, grados, secciones, matrículas, cursos y carga académica controlan alumnos y docentes asignados
- Docentes registran notas solo de sus cargas; alumnos y padres consultan únicamente las propias o vinculadas
- Rankings automáticos, libretas/reportes y notificación por panel responsive y correo al publicar

### Incidencias y Psicología

- Auxiliar registra en Cuaderno de Incidencias virtual
- Auxiliar deriva a TOE; TOE también puede registrar, gestionar, resolver y derivar a Psicología
- Psicología: registro confidencial visible por Psicología y `superadmin` (Promotor)

---

## Acuerdos Cerrados

| # | Acuerdo                               | Resultado                                               |
|---|---------------------------------------|---------------------------------------------------------|
| 1 | Lista definitiva de módulos para v1.0 | ✅ 8 módulos aprobados                                   |
| 2 | Roles del sistema                     | ✅ 10 roles específicos definidos                        |
| 3 | Stack tecnológico                     | ✅ Laravel 13 + React/Vite + PostgreSQL 16 (Monorepo) |
| 4 | Notificaciones                        | ✅ Panel responsive y correo electrónico                 |
| 5 | Esquema de base de datos              | ✅ UUID en dominio; BIGSERIAL en auditoría. Spatie para roles |
| 6 | Modalidad de entrega                  | ✅ Pago único + mantenimiento mensual                    |
| 7 | Repositorio                           | ✅ `iovargasjeff/CienciasNET`                            |
| 8 | Asistencia facial                     | ✅ Servicio Python en VPS + R2 privado + supervisión humana |

---

## Propuesta Económica Aprobada

| Concepto                               | Monto                     |
|----------------------------------------|---------------------------|
| Desarrollo completo (pago único)       | S/. 5,000 – S/. 8,000     |
| Capacitación + migración de datos      | Incluido                  |
| Mantenimiento mensual                  | S/. 300 – S/. 450/mes     |
| Hosting, dominio, SSL, backups diarios | Incluido en mantenimiento |
| Soporte WhatsApp y correo (Lun–Vie)    | Incluido en mantenimiento |

---

*Documento preparado por el Equipo CienciasNET. Reunión realizada y requerimientos aprobados por la dirección del
Colegio Ciencias.*
