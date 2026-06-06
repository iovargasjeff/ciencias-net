# add-student-attendance-supervision Specification

## Purpose

Permitir al Auxiliar supervisar jornadas y resolver excepciones.

## ADDED Requirements

### Requirement 1

Auxiliar SHALL resolver reconocimiento dudoso

#### Scenario: tablero refleja decisión

- GIVEN evento queda pendiente
- WHEN auxiliar confirma o rechaza
- THEN tablero refleja decisión

### Requirement 2

UI SHALL no inventar salida

#### Scenario: debe registrar motivo y hora real

- GIVEN hay ingreso sin salida
- WHEN auxiliar revisa anomalía
- THEN debe registrar motivo y hora real

