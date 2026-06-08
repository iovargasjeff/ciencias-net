# Tasks: add-facial-service-integration

## Source of Truth Check

- Product docs reviewed:
- Architecture docs reviewed:
- API contracts reviewed:
- Domain docs reviewed:
- Security docs reviewed:
- Conflicts found: yes/no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


## Implementation
- [x] 1.1 Implementar cliente Laravel y API FastAPI. Owner: André
- [x] 1.2 Autenticar red privada y sincronización. Owner: André
- [x] 1.3 Aplicar timeout y circuit handling. Owner: André
- [x] 1.4 Agregar pruebas de contrato. Owner: André

## Verification
- [x] 2.1 Verificar que contrato Laravel/Python pasa. Owner: André
- [x] 2.2 Verificar que servicio no accede PostgreSQL. Owner: André
- [x] 2.3 Verificar que timeout no crea asistencia. Owner: André

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson


## Source of Truth Check

- Product docs reviewed: roles/permisos no cambian.
- Architecture docs reviewed: facial integration and detailed system design.
- API contracts reviewed: `../../../../docs/api/internal/facial-openapi.yaml`; unchanged.
- Domain docs reviewed: Fase 2 attendance/facial OpenSpec artifacts.
- Security docs reviewed: authentication/authorization for private facial service.
- Conflicts found: no.

## Backend Placement

All backend domain code remains under existing modules; this iteration changes only the private FastAPI engine and Laravel facial-service exception import.
