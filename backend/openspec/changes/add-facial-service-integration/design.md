# Design: add-facial-service-integration

## Sources and Invariants

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/detailed-system-design.md`
- `../../../../docs/security/authentication-authorization.md`

## Technical Design

- Implementar cliente Laravel y API FastAPI.
- Autenticar red privada y sincronización.
- Aplicar timeout y circuit handling.
- Agregar pruebas de contrato.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- contrato Laravel/Python pasa.
- servicio no accede PostgreSQL.
- timeout no crea asistencia.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
