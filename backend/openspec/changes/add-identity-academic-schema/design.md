# Design: add-identity-academic-schema

## Sources and Invariants

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/academic.md`

## Technical Design

- Crear migraciones en orden de FK.
- Agregar constraints, índices, vigencias y audit_logs.
- Crear modelos, factories y seeders.
- Documentar rollback.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- migrate y rollback completos.
- constraints familiares y académicos pasan.
- EXPLAIN usa índices principales.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
