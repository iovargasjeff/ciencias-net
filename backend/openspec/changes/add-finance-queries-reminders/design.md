# Design: add-finance-queries-reminders

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
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear queries optimizadas y resources.
- Aplicar Policies familiares.
- Crear jobs de recordatorio.
- Exponer reportes paginados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- alcance familiar probado.
- valores cambian al vencer fecha.
- EXPLAIN de morosos/caja revisado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
