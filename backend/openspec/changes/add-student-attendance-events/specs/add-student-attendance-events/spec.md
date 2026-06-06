# add-student-attendance-events Specification

## Purpose

Convertir capturas válidas en entradas, salidas y reingresos de alumnos.

## ADDED Requirements

### Requirement 1

El primer evento bidireccional SHALL ser ingreso y los siguientes alternar

#### Scenario: se registran ingreso y salida

- GIVEN un alumno no tiene eventos hoy
- WHEN pasa dos veces
- THEN se registran ingreso y salida

### Requirement 2

Una salida de emergencia SHALL ser manual y notificar al padre

#### Scenario: queda movimiento auditado y notificación

- GIVEN auxiliar registra emergencia
- WHEN confirma salida
- THEN queda movimiento auditado y notificación

