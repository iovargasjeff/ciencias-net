# Design: add-production-deployment-backups

## Source of Truth Check

- Product docs reviewed: `docs/README.md`
- Architecture docs reviewed: `docs/architecture/deployment.md`, `docs/architecture/deployment-runbook.md`
- API contracts reviewed: `backend/openspec/API_CONTRACTS.md` declares no new HTTP contract
- Domain docs reviewed: `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/overview.md`, `docs/security/audit-and-operations.md`, `docs/security/data-and-files.md`
- Conflicts found: no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
No backend domain code is required for this operations-only change.
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


## Sources and Invariants

- `../../../../docs/architecture/deployment.md`
- `../../../../docs/architecture/deployment-runbook.md`

## Technical Design

- Crear configuración producción y secretos externos.
- Automatizar backup integral y checksums.
- Documentar restore y rollback.
- Probar restauración aislada.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- puertos privados verificados.
- restore trimestral documentado.
- alerta ante backup fallido.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
