# add-student-attendance-closure-review Specification

## Purpose

Cerrar jornadas y resolver excepciones sin inventar movimientos.

## ADDED Requirements

### Requirement 1

La falta SHALL generarse al cierre solo si no hubo ingreso

#### Scenario: se crea falta

- GIVEN terminó la jornada y alumno no ingresó
- WHEN corre cierre
- THEN se crea falta

### Requirement 2

El sistema SHALL no inventar salida faltante

#### Scenario: crea anomalía para Auxiliar

- GIVEN hay ingreso sin salida
- WHEN cierra jornada
- THEN crea anomalía para Auxiliar

