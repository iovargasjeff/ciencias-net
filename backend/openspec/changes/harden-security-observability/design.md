# Design: harden-security-observability

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
