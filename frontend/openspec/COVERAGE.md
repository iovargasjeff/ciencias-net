# Cobertura Frontend

Esta matriz conecta las experiencias aprobadas con los changes frontend. Cada feature depende del contrato backend
aceptado indicado en `EXECUTION_PLAN.md`.

| Experiencia documentada | Changes principales |
|---|---|
| Fundación visual, routing, sesión y calidad | `FE-001` a `FE-004` |
| Administración de cuentas y roles | `FE-005` |
| Vínculos familiares | `FE-006` |
| Estructura académica | `FE-007` |
| Portal padre/alumno y selector de hijo | `FE-008` |
| Activación y captura de estación web | `FE-009` |
| Consentimiento, enrolamiento y administración de dispositivos | `FE-009A` |
| Supervisión de asistencia de alumnos | `FE-010` |
| Asistencia docente y planilla | `FE-011` |
| Configuración financiera y beneficios | `FE-012` |
| Obligaciones, ajustes y pagos manuales | `FE-013` |
| Estado de cuenta familiar y reportes financieros | `FE-014` |
| Evaluaciones y carga de resultados | `FE-015` |
| Publicación, ranking, notas y reportes | `FE-016` |
| Materiales | `FE-017` |
| Horarios y calendario | `FE-018` |
| Comunicados y notificaciones | `FE-019` |
| Incidencias Auxiliar/TOE | `FE-020` |
| Psicología confidencial | `FE-021` |
| Accesibilidad, rendimiento y release | `FE-022`, `FE-023` |

## Reglas transversales

- Phosphor Icons es la única librería de iconos.
- Toda pantalla contempla carga, vacío, error, éxito y sin permiso.
- El backend siempre vuelve a autorizar.
- Padres y alumnos solo consultan recursos propios o vinculados.
- Las rutas de estación técnica permanecen separadas del portal humano.
