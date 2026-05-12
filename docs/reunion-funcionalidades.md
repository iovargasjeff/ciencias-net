# CienciasNET — Acta de Reunión de Definición de Funcionalidades

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
| `superadmin`            | Promotor / Directora  | Acceso total e irrestricto a todos los módulos, reportes y configuraciones                                                   |
| `toe`                   | Dpto. TOE             | Coordinación de docentes, recepción de reportes de Auxiliar, escalar a Psicología, notificación a padres sobre faltas graves |
| `psicologia`            | Psicóloga(o)          | Acceso a casos derivados, registro de atenciones confidenciales, soporte emocional                                           |
| `auxiliar`              | Auxiliar de Educación | Control de puerta (7:45 AM), registro de asistencias/tardanzas/faltas, Cuaderno de Incidencias virtual                       |
| `coordinador_academico` | Coord. Académico      | Malla curricular, exámenes semanales (viernes a martes), notas y rankings                                                    |
| `administrativo`        | Yanina (Contabilidad) | Gestión financiera, pagos, mensualidades, control de asistencia/tardanzas de docentes para planilla                          |
| `docente`               | Profesores            | Visualización de horario. No registran su propia asistencia                                                                  |
| `padre`                 | Padres / Apoderados   | Portal para ver notas, asistencias, incidencias y estado de cuenta de sus hijos                                              |

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
| 8 | Comunicados                                 | Dirección / TOE / Coordinador | ✅    |

---

## Reglas de Negocio Clave Aprobadas

### Asistencia Alumnos

- Horario límite: 7:45 AM
- Correo automático al padre en ingreso y salida
- 3 faltas = alerta automática a TOE y Auxiliar para citar al padre
- Solo TOE o Auxiliar pueden justificar faltas

### Asistencia Docentes (Planilla)

- Administrado exclusivamente por Yanina
- Minutos de tardanza acumulables mensualmente
- Falta justificada: descuento de horas no laboradas
- Falta injustificada: descuento × 2
- Registro de docente sustituto

### Finanzas

- Cuota de Ingreso: S/ 200.00, Matrícula: S/ 480.00, Mensualidad Base: S/ 480.00
- Descuento por pronto pago: S/ 30.00 (si paga hasta fin de mes)
- Etiquetas por alumno: normal, becado, descuento

### Evaluación

- Exámenes viernes, publicación martes
- 1°-4°: 40 preguntas. 5°: 60 preguntas (ciencias/letras)
- Rankings automáticos al publicar

### Incidencias y Psicología

- Auxiliar registra en Cuaderno de Incidencias virtual
- TOE puede derivar a Psicología
- Psicología: registro confidencial (solo visible por Psicología y Dirección)

---

## Acuerdos Cerrados

| # | Acuerdo                               | Resultado                                               |
|---|---------------------------------------|---------------------------------------------------------|
| 1 | Lista definitiva de módulos para v1.0 | ✅ 8 módulos aprobados                                   |
| 2 | Roles del sistema                     | ✅ 8 roles específicos definidos                         |
| 3 | Stack tecnológico                     | ✅ Laravel 11 + React 18/Vite + PostgreSQL 16 (Monorepo) |
| 4 | Notificaciones                        | ✅ Exclusivamente por correo electrónico                 |
| 5 | Esquema de base de datos              | ✅ UUID PKs en todas las tablas. Spatie para roles       |
| 6 | Modalidad de entrega                  | ✅ Pago único + mantenimiento mensual                    |
| 7 | Repositorio                           | ✅ `iovargasjeff/CienciasNET`                            |

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
