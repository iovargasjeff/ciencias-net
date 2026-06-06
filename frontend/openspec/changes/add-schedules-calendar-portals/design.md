# Design: add-schedules-calendar-portals

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear editor y vistas responsive.
- Crear calendario mensual.
- Integrar contexto familiar.
- Mostrar conflictos/estados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- vistas por rol pasan.
- conflicto visible.
- móvil usable.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
