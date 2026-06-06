# add-teacher-attendance-sessions Specification

## Purpose

Relacionar asistencia docente con clases programadas, cancelaciones y sustitutos.

## ADDED Requirements

### Requirement 1

Una clase cancelada SHALL no generar falta

#### Scenario: no se crea descuento

- GIVEN coordinación cancela sesión
- WHEN termina su horario
- THEN no se crea descuento

### Requirement 2

Una clase sin asistencia SHALL generar falta al finalizar

#### Scenario: queda falta docente

- GIVEN docente no asistió
- WHEN termina la clase
- THEN queda falta docente

