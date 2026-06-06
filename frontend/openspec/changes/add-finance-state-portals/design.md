# Design: add-finance-state-portals

## Sources and Invariants

- `../../../../docs/domain/finance.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear estado de cuenta responsive.
- Crear vistas morosos/caja autorizadas.
- Mostrar recibos y estados.
- Integrar selector familiar.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- montos por fecha probados.
- alcance familiar E2E.
- sin acciones de pago online.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
