# Proposal: add-payment-movements-receipts

**ID:** BE-016  
**Fase:** Fase 3: Finanzas  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** BE-015

## Why

Registrar pagos completos y correcciones como movimientos inmutables.

## In Scope

- pago exacto por efectivo, transferencia, Yape, Plin u otro
- recibo secuencial y comprobante privado
- anulación y devolución sin borrar historial

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: pago exacto por efectivo, transferencia, Yape, Plin u otro, recibo secuencial y comprobante privado, anulación y devolución sin borrar historial.

## Source Documents

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/data-and-files.md`
