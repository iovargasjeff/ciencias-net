# Verification: add-facial-service-integration

## Automated and Manual Checks

- [x] contrato Laravel/Python pasa.
- [x] servicio no accede PostgreSQL.
- [x] timeout no crea asistencia.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- 2026-06-07: Iniciado en rama `feature/facial-asistencia` después del commit BE-007 `ac5a556`.
- Context7/ctx7 usado para FastAPI: `/fastapi/fastapi`, docs de `UploadFile`, `File`, `Form` y headers/dependencies.
- Contrato: `add-facial-service-integration` implementa `API-FACIAL-INTERNAL` en `docs/api/internal/facial-openapi.yaml`; no agrega endpoints públicos.
- Laravel: agregado `App\Support\Facial\FacialServiceClient`, `FacialServiceUnavailable`, `config/facial-service.php`, timeout `FACIAL_SERVICE_TIMEOUT=5` y token interno `X-Facial-Service-Token`.
- Docker/entorno: `docker-compose.yml` ahora pasa `FACIAL_SERVICE_TOKEN` y `FACIAL_SERVICE_TIMEOUT` al backend; `facial-api` sigue solo en red privada.
- BE-007 queda integrado al cliente facial: el enrolamiento llama `/v1/enrollments`; si Python excede/falla, Laravel responde 503 y no persiste `perfiles_faciales`.
- FastAPI: implementados `/health`, `/v1/enrollments` y `/v1/identifications` con multipart, token interno, `Idempotency-Key`, errores 403/422 y sin acceso directo a PostgreSQL.
- Referencia `andre-carbajal/FaceDetectionApi`: se adaptó el enfoque de API de procesamiento facial hacia FastAPI privado sin Flask, pyodbc ni consultas SQL; Laravel conserva la autoridad y envía candidatos opacos.
- `docker compose build facial-api`: PASS.
- `docker compose up -d facial-api`: PASS.
- Contrato Python real dentro del contenedor con `urllib`: `/v1/enrollments` 200, `/v1/identifications` 200, solicitud sin token 403.
- `grep -RIn "psycopg\|postgres\|pyodbc\|sqlalchemy\|mysql" facial-service`: sin resultados.
- `docker compose exec backend php vendor/bin/pint --test`: PASS, 114 files.
- `docker compose exec backend php artisan test --filter=FacialServiceIntegrationTest`: PASS, 4 tests / 9 assertions.
- `docker compose exec backend php artisan test`: PASS, 38 tests / 151 assertions.
- `cd backend && openspec validate --strict --all`: PASS, 36 items.
- Pendiente: revisión del usuario antes de commit; no se archiva el change ni se marca `[x]` en `EXECUTION_PLAN.md` todavía.
- 2026-06-08: Rama `feature/facial-recognition-engine`: se reemplazó el placeholder `cienciasnet-digest-face-v1` por `face_recognition` (`cienciasnet-face-recognition-v1`) en FastAPI, manteniendo `/v1/enrollments` y `/v1/identifications` sin cambios de contrato.
- 2026-06-08: `facial-service` ahora usa `face_locations`, `face_encodings` y `face_distance`; exige exactamente un rostro por imagen, genera embeddings base64 de 128 floats y compara candidatos opacos enviados por Laravel.
- 2026-06-08: Se corrigió `StationController` para capturar `App\Modules\Usuarios\Infrastructure\Facial\FacialServiceUnavailable`, evitando que un timeout/falla facial salte el flujo de revisión/manual.
- 2026-06-08: Context7/ctx7 usado para `face_recognition`: `/ageitgey/face_recognition`, docs de detección, encodings y comparación.
- 2026-06-08: `docker compose build facial-api`: PASS; se agregó `setuptools==80.9.0` porque `face_recognition_models` requiere `pkg_resources` en `python:3.12-slim`.
- 2026-06-08: Import runtime dentro de la imagen como `nobody`: PASS (`face_recognition import ok`, modelo `.dat` localizado).
- 2026-06-08: `grep -RInE "psycopg|postgres|pyodbc|sqlalchemy|mysql|flask" facial-service`: sin resultados.
- 2026-06-08: `docker compose exec backend php artisan test --filter=FacialServiceIntegrationTest`: PASS, 4 tests / 9 assertions.
- 2026-06-08: `docker compose exec backend php artisan test --filter=StudentAttendanceEventsTest`: PASS, 4 tests / 30 assertions.
- 2026-06-08: `docker compose exec backend php artisan test`: PASS, 83 tests / 328 assertions.
- 2026-06-08: `cd backend && openspec validate --strict --all`: PASS, 30 items.
- 2026-06-08: `docker compose exec backend php vendor/bin/pint --test`: PASS, 190 files.
