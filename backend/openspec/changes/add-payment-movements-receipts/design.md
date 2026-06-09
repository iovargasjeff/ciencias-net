# Design: add-payment-movements-receipts

## Source of Truth Check

- Product docs reviewed:
- Architecture docs reviewed:
- API contracts reviewed:
- Domain docs reviewed:
- Security docs reviewed:
- Conflicts found: yes/no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


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
