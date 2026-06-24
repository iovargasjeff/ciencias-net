# Verification: add-production-deployment-backups

## Automated and Manual Checks

- [x] puertos privados verificados.
- [x] restore trimestral documentado.
- [x] alerta ante backup fallido.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `$env:CIENCIASNET_ENV_FILE='.env.production.example'; docker compose --env-file .env.production.example -f docker-compose.production.yml config --quiet`: PASS.
- `powershell -ExecutionPolicy Bypass -File ops/production/verify-compose-ports.ps1 -ComposeFile docker-compose.production.yml -EnvFile .env.production.example`: PASS, confirma puertos privados salvo frontend reverse-proxy local.
- `docker run --rm -v ${PWD}:/repo -w /repo alpine:3.20 sh -n ops/backup/backup.sh`: PASS.
- `docker run --rm -v ${PWD}:/repo -w /repo alpine:3.20 sh -n ops/backup/restore.sh`: PASS.
- `docker-compose.production.yml` agrega servicios production para `backend`, `api`, `queue`, `scheduler`, `facial-api`, `frontend` y perfil operacional `backup`; PostgreSQL, PHP-FPM, API Nginx, colas, scheduler y facial no exponen puertos host.
- `ops/backup/backup.sh` genera dump PostgreSQL, archivo de privados, inventario, checksum SHA-256, cifrado AES-256-CBC y alerta por webhook ante fallo si `BACKUP_ALERT_WEBHOOK_URL` está configurado.
- `ops/production/README.md` documenta restauración trimestral aislada y smoke checks posteriores.
- Fila contractual validada: `add-production-deployment-backups` declara `Sin contrato HTTP`, por lo que no se agregaron endpoints.
