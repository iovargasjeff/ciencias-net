# Verification: add-teacher-payroll-liquidation

## Automated and Manual Checks

- [x] fórmulas justificadas/injustificadas pasan.
- [x] tarifa histórica inmutable.
- [x] cierre bloquea cambios.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `docker compose exec backend php vendor/bin/pint` — OK, 156 files inspected, 1 style issue fixed.
- `docker compose exec backend php artisan test --filter=TeacherPayrollLiquidationTest` — OK, 5 passed (26 assertions).
- `docker compose exec backend php artisan test` — OK, 64 passed (295 assertions).
- `openspec validate --strict --all` — OK, 36 passed, 0 failed.
- API contract reviewed in `docs/api/paths/teacher-attendance.yaml`, `docs/api/schemas/teacher-attendance.yaml`, and `docs/api/request-bodies/teacher-attendance.yaml`; no endpoint was added outside OpenAPI.
- Negative authorization and errors covered: unauthenticated liquidation request (401), validation (422), close without permission (403), close missing liquidation (404), overlapping tariff and closed recalculation conflicts (409).
- Payroll close freezes rows by moving liquidations to `cerrada`; recalculation rejects closed periods and keeps historical rate snapshots immutable.
