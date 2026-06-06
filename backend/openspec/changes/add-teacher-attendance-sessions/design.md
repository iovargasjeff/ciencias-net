# Design: add-teacher-attendance-sessions

## Sources and Invariants

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Crear sesiones desde horarios.
- Implementar detección de falta/tardanza.
- Registrar cancelación y sustituto.
- Aplicar Policies y auditoría.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- primera/única clase calcula tardanza.
- cancelación evita falta.
- docente no corrige su asistencia.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
