# Design: add-family-links-administration

## Sources and Invariants

- `../../../../docs/domain/identity-access.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear páginas y buscadores familiares.
- Implementar formularios y diálogo de vínculo.
- Conectar queries/mutations.
- Cubrir estados y permisos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- N:M visible correctamente.
- desvinculación confirmada.
- sin autorregistro expuesto.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
