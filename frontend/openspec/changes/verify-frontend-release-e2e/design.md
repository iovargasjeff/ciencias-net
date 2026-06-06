# Design: verify-frontend-release-e2e

## Sources and Invariants

- `../../../../docs/product/approved-requirements.md`
- `../../../../docs/architecture/frontend.md`
- `../../../../docs/security/overview.md`

## Technical Design

- Ejecutar matriz E2E completa.
- Revisar permisos y contextos.
- Validar build/accesibilidad/consola.
- Registrar evidencia y pendientes.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- E2E por rol pasa.
- sin mocks ni errores consola.
- móvil/tablet/escritorio pasan.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
