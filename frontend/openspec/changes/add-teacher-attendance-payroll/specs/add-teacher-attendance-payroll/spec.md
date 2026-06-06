# add-teacher-attendance-payroll Specification

## Purpose

Permitir a Yanina revisar asistencia docente y cerrar planilla.

## ADDED Requirements

### Requirement 1

Yanina SHALL ver cálculo explicable

#### Scenario: ve fórmula, tarifa y monto

- GIVEN existe tardanza o falta
- WHEN abre liquidación
- THEN ve fórmula, tarifa y monto

### Requirement 2

Cerrar SHALL requerir confirmación explícita

#### Scenario: UI queda en solo lectura

- GIVEN liquidación está revisada
- WHEN Yanina confirma
- THEN UI queda en solo lectura

