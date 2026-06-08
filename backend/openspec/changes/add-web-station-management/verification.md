# Verification: add-web-station-management

## Automated and Manual Checks

- [x] código usado dos veces falla.
- [x] estación revocada pierde acceso.
- [x] rutas humanas bloqueadas.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- 2026-06-07: Iniciado en rama `feature/facial-asistencia` después del commit BE-008 `6b5dd31`.
- Contrato: `add-web-station-management` implementa `API-STATIONS` con `paths/stations.yaml`, `request-bodies/stations.yaml` y `schemas/stations.yaml`.
- Endpoints implementados: `GET/POST/PATCH /api/v1/stations`, `POST /api/v1/stations/{stationId}/activation-codes`, `POST /api/v1/stations/{stationId}/revocation`, `GET/POST /api/v1/stations/{stationId}/cameras`, `POST /api/v1/station-activations`, `GET /api/v1/station/session`, `POST /api/v1/station/captures`.
- Sesión técnica: cookie `httpOnly` `cienciasnet_station_session` y fallback `Authorization: Bearer` para clientes técnicos; no usa sesión humana ni filas `users`.
- Seguridad: middleware `station.session` busca `cuentas_tecnicas` activas por hash SHA-256, valida estación activa/no revocada y scopes `station:status`/`station:capture`.
- Activación: códigos hasheados, un solo uso, expiración máxima 10 minutos por constraint DB; al activar rota token técnico y marca estación activada.
- Revocación: desactiva estación y cuenta técnica, rota estado y borra cookie de sesión técnica en respuesta.
- Captura: endpoint técnico valida cámara de la estación e idempotencia; crea `eventos_reconocimiento` pendiente sin crear asistencia (BE-010 decidirá asistencia).
- Auditoría: creación, actualización, código de activación, activación y revocación registradas sin guardar códigos/tokens en claro.
- `docker compose exec backend php vendor/bin/pint --test`: PASS, 125 files.
- `docker compose exec backend php artisan test --filter=WebStationManagementTest`: PASS, 4 tests / 29 assertions.
- `docker compose exec backend php artisan test`: PASS, 42 tests / 180 assertions.
- `cd backend && openspec validate --strict --all`: PASS, 36 items.
- Pendiente: revisión del usuario antes de commit; no se archiva el change ni se marca `[x]` en `EXECUTION_PLAN.md` todavía.
