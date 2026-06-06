# Design: add-payment-movements-receipts

## Sources and Invariants

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/data-and-files.md`

## Technical Design

- Implementar registro de pago transaccional.
- Calcular monto exigible por fecha.
- Generar recibo PDF y guardar comprobante privado.
- Implementar anulación/devolución.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- pagos parciales rechazados.
- referencia duplicada rechazada.
- historial permanece inmutable.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
