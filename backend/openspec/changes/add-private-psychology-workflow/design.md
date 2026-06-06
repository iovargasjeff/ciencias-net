# Design: add-private-psychology-workflow

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/security/audit-and-operations.md`

## Technical Design

- Crear casos de uso privados.
- Implementar Policies explícitas.
- Separar Resources públicos/privados.
- Auditar accesos sensibles.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- matriz negativa completa.
- logs no contienen notas.
- superadmin y Psicología autorizados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
