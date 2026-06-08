# Verification: add-biometric-attendance-schema

## Automated and Manual Checks

- [x] constraints biométricos pasan.
- [x] índices de pendientes verificados.
- [x] rollback completo.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- 2026-06-07: Iniciado en rama `feature/facial-asistencia`.
- Contrato: `add-biometric-attendance-schema` declara `Sin contrato HTTP`; no se agregaron endpoints ni cambios en Scribe.
- Documentos revisados: `docs/architecture/database-schema.md`, `docs/architecture/facial-integration.md`, `docs/security/data-and-files.md`.
- Implementado en `database/migrations/2026_06_07_010000_create_biometric_attendance_tables.php` con tablas biométricas, estaciones, eventos de reconocimiento, asistencia, anomalías, jornadas, sesiones docentes, tarifas y liquidaciones.
- Se agregaron modelos Eloquent mínimos para las nuevas tablas bajo `app/Models/`.
- Se agregó `tests/Feature/BiometricAttendanceSchemaTest.php` para tablas, constraints, índice parcial de pendientes, expiración de activaciones y rollback.
- `docker compose exec backend php vendor/bin/pint --test`: PASS, 100 files.
- `docker compose exec backend php artisan test --filter=BiometricAttendanceSchemaTest`: PASS, 7 tests / 26 assertions.
- `docker compose exec backend php artisan test`: PASS, 28 tests / 108 assertions.
- `cd backend && openspec validate --strict --all`: PASS, 36 items.
- Privacidad: la migración solo guarda hashes/tokens/keys privadas; no crea endpoints ni exposición pública de biometría.
- Pendiente: revisión del usuario antes de commit; no se archiva el change ni se marca `[x]` en `EXECUTION_PLAN.md` todavía.
