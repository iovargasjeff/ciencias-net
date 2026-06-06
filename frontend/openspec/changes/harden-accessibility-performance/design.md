# Design: harden-accessibility-performance

## Sources and Invariants

- `../../../../docs/architecture/frontend.md`
- `../../../../docs/security/overview.md`

## Technical Design

- Auditar componentes y rutas.
- Optimizar bundles/queries/tablas.
- Corregir foco, contraste y motion.
- Agregar pruebas automáticas.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- axe sin fallos críticos.
- presupuesto bundle aceptado.
- responsive completo.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
