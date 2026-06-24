# Tasks: harden-security-observability

## Source of Truth Check

- Product docs reviewed: `docs/README.md`
- Architecture docs reviewed: `docs/architecture/backend.md`, `docs/architecture/deployment.md`
- API contracts reviewed: `docs/api/openapi.yaml`, `docs/api/parameters/common.yaml`, `docs/api/responses/common.yaml`, `docs/api/schemas/common.yaml`, `docs/api/security-schemes/common.yaml`
- Domain docs reviewed: `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/overview.md`, `docs/security/audit-and-operations.md`, `docs/security/data-and-files.md`
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
- [x] 1.1 Configurar controles HTTP y rate limits. Owner: André
- [x] 1.2 Instrumentar auditoría y correlación. Owner: André
- [x] 1.3 Agregar métricas/alertas. Owner: André
- [x] 1.4 Revisar redacción de datos. Owner: André

## Verification
- [x] 2.1 Verificar que escaneo de logs sin sensibles. Owner: André
- [x] 2.2 Verificar que rate limits probados. Owner: André
- [x] 2.3 Verificar que eventos críticos auditados. Owner: André

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
