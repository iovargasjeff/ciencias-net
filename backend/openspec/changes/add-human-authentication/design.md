# Design: add-human-authentication

## Sources and Invariants

- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/domain/identity-access.md`

## Technical Design

- Implementar endpoints de sesión y recuperación.
- Configurar Sanctum stateful, CSRF y cookies.
- Auditar intentos y logout.
- Aplicar rate limits.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- login/logout/419 probados.
- recuperación no filtra existencia.
- cuenta desactivada rechazada.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
