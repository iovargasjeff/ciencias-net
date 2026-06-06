# Design: define-api-contract-conventions

## Sources and Invariants

- `../../../../docs/architecture/backend.md`
- `../../../../docs/security/overview.md`

## Technical Design

- Crear response/error conventions.
- Configurar exception handler y paginación.
- Definir middleware de idempotencia.
- Publicar OpenAPI inicial.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- pruebas de errores y paginación pasan.
- Scribe genera contrato.
- reintento idempotente no duplica.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
