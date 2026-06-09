# Design: add-teacher-attendance-payroll

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear panel docente/planilla.
- Crear formularios de tarifa/corrección/sustituto.
- Mostrar desglose y estados.
- Implementar cierre y reporte.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- fórmulas visibles.
- cierre bloquea UI.
- permiso gestionar_planilla probado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
