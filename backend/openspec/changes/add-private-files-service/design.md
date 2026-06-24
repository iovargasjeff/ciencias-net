# Design: add-private-files-service

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


## Sources and Invariants

- `../../../../docs/security/data-and-files.md`
- `../../../../docs/architecture/deployment.md`

## Technical Design

- Crear servicio de archivos privados.
- Validar MIME/tamaño/checksum.
- Implementar URLs firmadas y limpieza.
- Agregar Policies y auditoría.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- storage:link no requerido.
- URL expira.
- limpieza elimina evidencia.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
