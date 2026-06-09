# Design: add-incidents-workflow

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear cuaderno y formulario.
- Crear detalle con timeline.
- Implementar derivación/notificación/resolución.
- Aplicar permisos visuales.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- flujo completo E2E.
- historial visible.
- padre ve solo lo permitido.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
