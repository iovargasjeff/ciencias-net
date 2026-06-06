# Design: add-schedules-calendar-management

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Crear casos de uso de horarios/calendario.
- Validar solapamientos y alcance.
- Exponer vistas por actor.
- Integrar sesiones de clase.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- solapamiento inválido rechazado.
- alcance familiar probado.
- día no laboral afecta sesiones.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
