# Design: add-private-psychology-portal

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/authentication-authorization.md`

## Technical Design

- Crear rutas/layout privado.
- Crear formularios y detalle protegido.
- Evitar previews sensibles.
- Cubrir estados y permisos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- matriz negativa E2E.
- sin notas en consola/URL.
- superadmin/Psicología pasan.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
