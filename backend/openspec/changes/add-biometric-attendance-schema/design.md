# Design: add-biometric-attendance-schema

## Sources and Invariants

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/data-and-files.md`

## Technical Design

- Crear migraciones biométricas y asistencia.
- Agregar checks e índices parciales.
- Modelar expiración y revocación.
- Probar rollback.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- constraints biométricos pasan.
- índices de pendientes verificados.
- rollback completo.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
