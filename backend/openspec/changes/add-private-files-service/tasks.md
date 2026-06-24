# Tasks: add-private-files-service

## Source of Truth Check

- Product docs reviewed: `docs/README.md`
- Architecture docs reviewed: `docs/architecture/backend.md`, `docs/architecture/deployment.md`
- API contracts reviewed: `docs/api/openapi.yaml`, `docs/api/paths/files.yaml`, `docs/api/request-bodies/files.yaml`, `docs/api/schemas/files.yaml`
- Domain docs reviewed: `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/data-and-files.md`, `docs/security/overview.md`
- Conflicts found: no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/Shared/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


## Implementation
- [x] 1.1 Crear servicio de archivos privados. Owner: André
- [x] 1.2 Validar MIME/tamaño/checksum. Owner: André
- [x] 1.3 Implementar URLs firmadas y limpieza. Owner: André
- [x] 1.4 Agregar Policies y auditoría. Owner: André

## Verification
- [x] 2.1 Verificar que storage:link no requerido. Owner: André
- [x] 2.2 Verificar que URL expira. Owner: André
- [x] 2.3 Verificar que limpieza elimina evidencia. Owner: André

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
