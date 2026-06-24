# Verification: verify-backend-release

## Automated and Manual Checks

- [x] suite y contrato pasan.
- [x] sin vulnerabilidades críticas.
- [x] restore y smoke producción pasan.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `docker compose run --rm backend php artisan test` - PASS, 197 tests / 692 assertions.
- `docker compose run --rm backend ./vendor/bin/pint --test` - PASS, 337 files after formatting `DemoCompleteSeeder.php`.
- `docker compose run --rm backend php scripts/guard-architecture.php` - PASS.
- `docker compose run --rm backend sh -lc 'php artisan migrate:rollback --step=2 --force && php artisan migrate --force'` - PASS after fixing BE-029 rollback constraints.
- `$env:CIENCIASNET_ENV_FILE='.env.production.example'; docker compose --env-file .env.production.example -f docker-compose.production.yml config --quiet` - PASS.
- `powershell -ExecutionPolicy Bypass -File ops/production/verify-compose-ports.ps1 -ComposeFile docker-compose.production.yml -EnvFile .env.production.example` - PASS, production compose ports are private except frontend reverse-proxy binding.
- `docker run --rm -v ${PWD}:/repo -w /repo alpine:3.20 sh -n ops/backup/backup.sh; docker run --rm -v ${PWD}:/repo -w /repo alpine:3.20 sh -n ops/backup/restore.sh` - PASS after normalizing scripts to LF and replacing fragile continuations.

## Pending Items

- No critical release blockers remain from automated verification.
- `CAMBIOS CIENCIASNET.docx` was not available locally; Fase 7 was verified against OpenSpec and repository docs.
