# ADR-003: Finanzas e Históricos

**Estado:** Aceptado

- No existen pagos parciales en V1.
- Cada deuda se paga en una operación completa.
- Pronto pago depende de una fecha límite configurable.
- La cuenta específica con `gestionar_finanzas` puede ajustar deudas pendientes con motivo y auditoría.
- Deudas pagadas/anuladas y movimientos históricos son inmutables.

La entidad que representa una deuda se denomina definitivamente `obligaciones_pago`. Los pagos reales permanecen en
`movimientos_pago`.
