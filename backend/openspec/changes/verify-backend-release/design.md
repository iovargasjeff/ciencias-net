# Design: verify-backend-release

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`; `CAMBIOS CIENCIASNET.docx` no está disponible localmente, por lo que OpenSpec se usa como fuente suficiente para este change.
- Architecture docs reviewed: `docs/architecture/backend.md`, `docs/architecture/database-schema.md`, `docs/architecture/deployment-runbook.md`
- API contracts reviewed: `docs/api/openapi.yaml`, paquetes modificados en Fase 6 y Fase 7
- Domain docs reviewed: `docs/domain/academic.md`, `docs/domain/identity-access.md`, `docs/domain/finance.md`, `docs/domain/incidents-communications.md`
- Security docs reviewed: `docs/security/README.md`, `docs/security/data-and-files.md`
- Conflicts found: no

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


## Sources and Invariants

- `../../../../docs/README.md`
- `../../../../docs/product/approved-requirements.md`
- `../../../../docs/security/overview.md`

## Technical Design

- Ejecutar suite completa.
- Revisar matriz de permisos y rendimiento.
- Validar OpenAPI, migraciones y backups.
- Registrar pendientes explícitos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- suite y contrato pasan.
- sin vulnerabilidades críticas.
- restore y smoke producción pasan.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
