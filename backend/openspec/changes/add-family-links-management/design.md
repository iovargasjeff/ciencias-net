# Design: add-family-links-management

## Sources and Invariants

- `../../../../docs/domain/identity-access.md`
- `../../../../docs/product/roles-and-permissions.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Implementar casos de uso familiares.
- Crear Policies por vínculo.
- Exponer endpoints y resources.
- Auditar vínculos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- N:M probado.
- acceso ajeno bloqueado.
- desvinculación conserva auditoría.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
