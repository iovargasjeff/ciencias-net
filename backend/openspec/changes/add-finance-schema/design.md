# Design: add-finance-schema

## Sources and Invariants

- `../../../../docs/domain/finance.md`
- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/decisions/003-finance-history.md`

## Technical Design

- Crear migraciones financieras.
- Agregar checks, FKs, índices y unicidad.
- Modelar estados e inmutabilidad.
- Probar consultas y rollback.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- constraints de montos pasan.
- referencias duplicadas fallan.
- rollback completo.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
