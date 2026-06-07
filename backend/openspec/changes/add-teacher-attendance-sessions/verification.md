# Verification: add-teacher-attendance-sessions

## Automated and Manual Checks

- [x] primera/única clase calcula tardanza.
- [x] cancelación evita falta.
- [x] clase sin asistencia genera falta docente.
- [x] docente no corrige su asistencia.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `docker compose exec backend php vendor/bin/pint` — OK, 149 files inspected, PASS.
- `docker compose exec backend php artisan test --filter=TeacherAttendanceSessionsTest` — OK, 6 passed (25 assertions).
- `docker compose exec backend php artisan test` — OK, 59 passed (269 assertions).
- `openspec validate --strict --all` — OK, 36 passed, 0 failed.
- API contract reviewed in `docs/api/paths/teacher-attendance.yaml` and `docs/api/schemas/teacher-attendance.yaml`; no endpoint was added outside OpenAPI.
- Negative authorization covered: unauthenticated list (401), missing permission (403), teacher self-correction blocked (403), validation (422), missing class session (404), and cancelled session substitute conflict (409).
- Cancellation and adjustment reasons are audited with redacted reason payloads.
