# Design: harden-security-observability

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


## Sources and Invariants

- `../../../../docs/security/overview.md`
- `../../../../docs/security/audit-and-operations.md`
- `../../../../docs/architecture/deployment.md`

## Technical Design

- Configurar controles HTTP y rate limits.
- Instrumentar auditoría y correlación.
- Agregar métricas/alertas.
- Revisar redacción de datos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- escaneo de logs sin sensibles.
- rate limits probados.
- eventos críticos auditados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
