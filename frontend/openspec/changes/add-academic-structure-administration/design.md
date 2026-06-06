# Design: add-academic-structure-administration

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear páginas académicas y DataTables.
- Crear formularios y selectores dependientes.
- Implementar queries/mutations.
- Mostrar vigencia y errores.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- flujos CRUD E2E.
- roles no autorizados bloqueados.
- tablas responsive.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
