# Design: add-communications-notifications

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear editor segmentado.
- Crear bandeja y contador.
- Implementar lectura/archivo.
- Cubrir permisos y estados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- segmentación E2E.
- lectura idempotente.
- roles de publicación probados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
