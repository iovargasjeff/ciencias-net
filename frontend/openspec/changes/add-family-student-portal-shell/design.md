# Design: add-family-student-portal-shell

## Sources and Invariants

- `../../../../docs/product/roles-and-permissions.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/data-and-files.md`

## Technical Design

- Crear portal y selector de contexto.
- Implementar navegación por permisos.
- Mostrar estado biométrico mínimo sin datos sensibles.
- Persistir contexto sin datos sensibles y cubrir estados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- contextos E2E.
- rutas de escritura ausentes.
- estado biométrico no expone datos.
- acceso ajeno bloqueado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
