# Verification: add-student-attendance-events

## Automated and Manual Checks

- [x] duplicado no crea movimiento.
- [x] alternancia y modo fijo probados.
- [x] padre recibe solo eventos de hijos.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- 2026-06-07: Iniciado en rama `feature/facial-asistencia` después del commit BE-009 `f798943`.
- Contrato: `add-student-attendance-events` implementa `API-STUDENT-ATTENDANCE` con `paths/student-attendance.yaml`, `request-bodies/student-attendance.yaml` y `schemas/student-attendance.yaml`.
- Endpoints implementados en este change: `GET /api/v1/student-attendance`, `POST /api/v1/student-attendance/manual-events`; `POST /api/v1/station/captures` ahora procesa reconocimiento facial y puede crear movimiento de alumno.
- Procesador: `StudentAttendanceProcessor` convierte reconocimiento aceptado en ingreso/salida/reingreso, aplica modo fijo de cámara, alternancia bidireccional y tardanza con límite por configuración o default 07:45.
- Idempotencia: `station/captures` y `manual-events` usan middleware `idempotent`; retry con misma key no duplica movimientos.
- Notificaciones: `StudentAttendanceMovementNotification` implementa `ShouldQueue`; padres vinculados con `recibe_notificaciones` reciben notificación y el movimiento marca `notificacion_enviada`.
- Historial autorizado: gestores operativos ven todo; padres solo alumnos vinculados; alumnos solo su propio historial.
- Seguridad: rutas manuales requieren rol `superadmin` o `auxiliar`; sesión técnica no puede consultar rutas humanas.
- `docker compose exec backend php vendor/bin/pint --test`: PASS, 132 files.
- `docker compose exec backend php artisan test --filter=StudentAttendanceEventsTest`: PASS, 4 tests / 30 assertions.
- `docker compose exec backend php artisan test`: PASS, 46 tests / 211 assertions.
- `cd backend && openspec validate --strict --all`: PASS, 36 items.
- Pendiente: revisión del usuario antes de commit; no se archiva el change ni se marca `[x]` en `EXECUTION_PLAN.md` todavía.
