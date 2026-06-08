# Design: add-obligation-generation-adjustments

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

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/audit-and-operations.md`

## Technical Design

- Implementar generación transaccional.
- Resolver beneficio único y acumulación.
- Crear ajuste individual/masivo auditado.
- Notificar afectados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- generación masiva no queda parcial.
- pagada/anulada no cambia.
- pronto pago congelado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
