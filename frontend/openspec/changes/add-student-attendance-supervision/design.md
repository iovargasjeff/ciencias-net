# Design: add-student-attendance-supervision

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear dashboard y filtros.
- Crear flujos de revisión/corrección.
- Implementar salida emergencia y justificación.
- Mostrar historial auditado.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- flujos E2E pasan.
- acciones requieren motivo.
- roles ajenos bloqueados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
