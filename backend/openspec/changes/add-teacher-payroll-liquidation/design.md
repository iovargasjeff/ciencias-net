# Design: add-teacher-payroll-liquidation

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Implementar tarifas con vigencia.
- Calcular fórmulas de descuento.
- Crear revisión y cierre transaccional.
- Generar reporte de planilla.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- fórmulas justificadas/injustificadas pasan.
- tarifa histórica inmutable.
- cierre bloquea cambios.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
