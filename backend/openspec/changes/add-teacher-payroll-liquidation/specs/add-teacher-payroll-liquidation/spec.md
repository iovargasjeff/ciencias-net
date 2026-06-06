# add-teacher-payroll-liquidation Specification

## Purpose

Calcular y cerrar descuentos docentes con tarifas históricas.

## ADDED Requirements

### Requirement 1

Cambiar tarifa SHALL afectar solo periodos futuros

#### Scenario: histórico conserva monto

- GIVEN existe liquidación histórica
- WHEN Yanina cambia tarifa
- THEN histórico conserva monto

### Requirement 2

Cerrar liquidación SHALL congelar resultados

#### Scenario: ya no admite edición directa

- GIVEN Yanina revisó el mes
- WHEN confirma cierre
- THEN ya no admite edición directa

