# Design: add-academic-structure-management

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear casos de uso y Policies académicas.
- Implementar endpoints CRUD y filtros.
- Proteger cambios con transacciones.
- Publicar OpenAPI.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- restricciones de vigencia pasan.
- docente no edita estructura.
- consultas paginadas usan índices.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
