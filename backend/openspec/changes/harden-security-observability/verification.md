# Verification: harden-security-observability

## Automated and Manual Checks

- [x] escaneo de logs sin sensibles.
- [x] rate limits probados.
- [x] eventos críticos auditados.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `docker compose run --rm backend php artisan test tests/Feature/SecurityObservabilityTest.php tests/Feature/ApiConventionsTest.php tests/Feature/HumanAuthenticationTest.php`: PASS, 15 tests / 72 assertions. Cubre headers seguros, `X-Request-Id`, rate limit 429, redacción de auditoría/contexto, health y autenticación.
- `docker compose run --rm backend ./vendor/bin/pint --test app/Http/Middleware/CorrelateRequest.php app/Http/Middleware/SecurityHeaders.php app/Support/SensitiveDataRedactor.php app/Support/RedactSensitiveLogContext.php app/Support/AuditLogger.php bootstrap/app.php config/logging.php app/Providers/AppServiceProvider.php routes/api.php tests/Feature/ApiConventionsTest.php tests/Feature/SecurityObservabilityTest.php`: PASS, 11 files.
- `docker compose run --rm backend php scripts/guard-architecture.php`: PASS.
- Contrato revisado: fila `harden-security-observability` de `API_CONTRACTS.md` apunta a `API-CORE` y seguridad común; se actualizaron `parameters/common.yaml`, `responses/common.yaml` y `schemas/common.yaml`.
- Nota operativa: la ejecución paralela de grupos de pruebas contra la misma base PostgreSQL compartida produjo carreras de migraciones; se repitió secuencialmente y pasó.
