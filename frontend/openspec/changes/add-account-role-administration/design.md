# Design: add-account-role-administration

## Sources and Invariants

- `../../../../docs/domain/identity-access.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear rutas/páginas de cuentas.
- Crear formularios Zod y permisos visuales.
- Implementar queries/mutations e invalidación.
- Añadir confirmaciones y auditoría visible.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- matriz visual probada.
- errores backend visibles.
- responsive y teclado pasan.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
