# Verification: add-private-files-service

## Automated and Manual Checks

- [x] storage:link no requerido.
- [x] URL expira.
- [x] limpieza elimina evidencia.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `docker compose run --rm backend php artisan test tests/Feature/PrivateFilesServiceTest.php`: PASS, 5 tests / 19 assertions. Cubre upload privado con checksum, rechazo por checksum inválido, 403 a usuario no autorizado, URL firmada expirable y limpieza auditada.
- `docker compose run --rm backend php artisan test tests/Feature/MaterialsTest.php`: PASS, 5 tests / 9 assertions. Confirma que el disco privado no depende de `storage:link` y Materiales sigue descargando por endpoint autorizado.
- `docker compose run --rm backend php scripts/guard-architecture.php`: PASS.
- `docker compose run --rm backend ./vendor/bin/pint --test app/Modules/Shared app/Providers/AppServiceProvider.php routes/api.php routes/console.php config/filesystems.php tests/Feature/PrivateFilesServiceTest.php database/migrations/2026_06_20_000001_create_private_files_table.php`: PASS, 13 files.
- Contrato revisado: fila `add-private-files-service` de `API_CONTRACTS.md` apunta a `paths/files.yaml`, `request-bodies/files.yaml` y `schemas/files.yaml`; se actualizaron metadatos de checksum/expiración sin agregar endpoints fuera del paquete `API-FILES`.
- No se registran secretos, biometría ni contenido de archivo en auditoría; solo metadatos permitidos como propósito, MIME, tamaño, checksum y expiración.
