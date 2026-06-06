# Design: add-communications-notifications

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Crear segmentación y queries.
- Enviar notificaciones por cola.
- Registrar lectura/archivo.
- Aplicar permisos de publicación.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- segmentación probada.
- lectura idempotente.
- fallo de correo reintentable.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
