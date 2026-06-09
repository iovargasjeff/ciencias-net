# Verification: add-biometric-enrollment-consent

## Automated and Manual Checks

- [x] enrolamiento sin consentimiento bloqueado.
- [x] revocación desactiva.
- [x] objetos privados no son públicos.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- 2026-06-07: Iniciado en rama `feature/facial-asistencia` después del commit DB-002 `8b05cbc`.
- Contrato: `add-biometric-enrollment-consent` implementa `API-BIOMETRICS` con `paths/biometrics.yaml`, `request-bodies/biometrics.yaml` y `schemas/biometrics.yaml`.
- Endpoints implementados: `GET/POST /api/v1/biometric-consents`, `POST /api/v1/biometric-consents/{consentId}/revocation`, `POST /api/v1/biometric-enrollments`.
- Se agregó migración `2026_06_07_020000_add_biometric_consent_contract_fields.php` para persistir `legal_basis`/`expires_at` contractuales como `fundamento_legal`/`expira_en`.
- Se agregó `BIOMETRIC_EMBEDDING_KEY` separado en `.env.example` y `config/biometrics.php`.
- El enrolamiento almacena imágenes en disco privado configurable (`BIOMETRIC_STORAGE_DISK`/`BIOMETRIC_STORAGE_PREFIX`) y nunca devuelve embeddings ni URLs públicas.
- El embedding queda cifrado con `App\Support\Biometrics\BiometricEmbeddingEncryptor`; hasta BE-008 se usa payload opaco de hashes de enrolamiento con `modelo_version=pending-facial-service-v1`.
- Revocación transaccional: cambia consentimiento a `revocado`, desactiva perfiles activos y agenda expiración de archivos privados.
- Auditoría agregada para otorgamiento, revocación y enrolamiento sin registrar imágenes, embeddings ni motivos sensibles completos.
- `docker compose exec backend php vendor/bin/pint --test`: PASS, 110 files.
- `docker compose exec backend php artisan test --filter=BiometricEnrollmentConsentTest`: PASS, 5 tests / 33 assertions.
- `docker compose exec backend php artisan test`: PASS, 34 tests / 142 assertions.
- `cd backend && openspec validate --strict --all`: PASS, 36 items.
- Pendiente: revisión del usuario antes de commit; no se archiva el change ni se marca `[x]` en `EXECUTION_PLAN.md` todavía.
