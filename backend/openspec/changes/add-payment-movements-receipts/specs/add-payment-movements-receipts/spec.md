# add-payment-movements-receipts Specification

## Purpose

Registrar pagos completos y correcciones como movimientos inmutables.

## ADDED Requirements

### Requirement 1

El pago SHALL ser completo por monto aplicable

#### Scenario: se rechaza

- GIVEN una obligación exige 450 hoy
- WHEN se intenta pagar otro monto
- THEN se rechaza

### Requirement 2

Corregir pago SHALL crear movimiento compensatorio

#### Scenario: original permanece y se registra anulación

- GIVEN un pago fue aplicado mal
- WHEN Yanina anula
- THEN original permanece y se registra anulación

