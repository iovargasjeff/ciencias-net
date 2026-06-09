# add-incidents-workflow Specification

## Purpose

Operar el cuaderno de incidencias y seguimiento TOE.

## ADDED Requirements

### Requirement: 1

Auxiliar SHALL registrar y derivar incidencia

#### Scenario: historial muestra ambas acciones

- GIVEN crea caso
- WHEN deriva a TOE
- THEN historial muestra ambas acciones

### Requirement: 2

TOE SHALL gestionar seguimiento

#### Scenario: estado e historial se actualizan

- GIVEN abre caso derivado
- WHEN comenta/notifica/resuelve
- THEN estado e historial se actualizan

