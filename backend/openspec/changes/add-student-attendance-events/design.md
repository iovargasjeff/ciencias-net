# Design: add-student-attendance-events

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/facial-integration.md`

## Technical Design

- Implementar procesador de eventos alumno.
- Aplicar idempotencia, modos y horarios.
- Crear notificaciones en cola.
- Exponer historial autorizado.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- duplicado no crea movimiento.
- alternancia y modo fijo probados.
- padre recibe solo eventos de hijos.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
