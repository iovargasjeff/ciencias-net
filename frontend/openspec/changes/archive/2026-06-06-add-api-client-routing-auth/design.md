# Design: add-api-client-routing-auth

## Sources and Invariants

- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/architecture/frontend.md`
- `../../../../docs/architecture/facial-integration.md`

## Technical Design

- Crear api client e interceptores.
- Crear AuthProvider, query client y pantallas de acceso/recuperación.
- Implementar router/layouts/guards y selección de contexto.
- Manejar sesión expirada y errores.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- login/logout/recuperación/419 E2E.
- localStorage sin tokens.
- estación bloqueada del portal.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
