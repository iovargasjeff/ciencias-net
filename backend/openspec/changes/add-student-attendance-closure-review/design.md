# Design: add-student-attendance-closure-review

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/audit-and-operations.md`

## Technical Design

- Crear jobs de cierre y alertas.
- Implementar revisión/corrección auditada.
- Autorizar Auxiliar y TOE para justificar.
- Manejar eventos capturados antes del cierre.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- cierre tardío corrige falta.
- solo injustificadas cuentan alerta.
- salida faltante genera anomalía.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
