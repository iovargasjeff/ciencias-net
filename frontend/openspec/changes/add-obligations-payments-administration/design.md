# Design: add-obligations-payments-administration

## Sources and Invariants

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear DataTables y filtros financieros.
- Crear generación/ajuste con preview.
- Crear registro pago y comprobante.
- Crear anulación/devolución y recibo.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- pago parcial imposible.
- acciones históricas confirmadas.
- upload y errores probados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
