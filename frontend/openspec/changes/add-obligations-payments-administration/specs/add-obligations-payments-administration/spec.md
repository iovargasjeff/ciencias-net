# add-obligations-payments-administration Specification

## Purpose

Gestionar obligaciones, ajustes y pagos manuales sin alterar históricos.

## ADDED Requirements

### Requirement 1

UI SHALL impedir pago parcial

#### Scenario: campo exige valor aplicable

- GIVEN obligación exige monto exacto
- WHEN Yanina registra pago
- THEN campo exige valor aplicable

### Requirement 2

Ajuste SHALL exigir motivo y mostrar afectados

#### Scenario: confirma con motivo

- GIVEN Yanina ajusta grupo
- WHEN previsualiza
- THEN confirma con motivo

