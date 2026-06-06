# Design: add-design-system-layouts

## Sources and Invariants

- `../../../../docs/architecture/frontend.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear tokens y componentes base.
- Implementar layouts y navegación responsive.
- Configurar Phosphor Icons y patrones de estados.
- Documentar uso de GSAP.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- WCAG básica pasa.
- responsive móvil/tablet/escritorio.
- reduced motion probado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
