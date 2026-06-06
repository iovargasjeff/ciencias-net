# add-finance-schema Specification

## Purpose

Persistir configuración, beneficios, obligaciones y movimientos financieros inmutables.

## ADDED Requirements

### Requirement 1

Una obligación SHALL congelar montos y fecha límite

#### Scenario: cambios futuros no alteran sus valores

- GIVEN se genera una deuda
- WHEN se guarda
- THEN cambios futuros no alteran sus valores

### Requirement 2

Una referencia SHALL ser única por medio/proveedor

#### Scenario: la base rechaza

- GIVEN ya existe referencia
- WHEN se registra otro pago
- THEN la base rechaza

