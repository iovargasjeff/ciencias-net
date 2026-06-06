# Design: add-obligation-generation-adjustments

## Sources and Invariants

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/audit-and-operations.md`

## Technical Design

- Implementar generación transaccional.
- Resolver beneficio único y acumulación.
- Crear ajuste individual/masivo auditado.
- Notificar afectados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- generación masiva no queda parcial.
- pagada/anulada no cambia.
- pronto pago congelado.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
