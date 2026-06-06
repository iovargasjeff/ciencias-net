# Design: add-roles-permissions-account-management

## Sources and Invariants

- `../../../../docs/product/roles-and-permissions.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/security/audit-and-operations.md`

## Technical Design

- Crear Policies y casos de uso de cuentas/roles.
- Sembrar roles y permisos.
- Auditar cambios sensibles.
- Exponer endpoints y OpenAPI.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- matriz positiva y negativa pasa.
- cambios propios bloqueados.
- desactivación conserva historial.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
