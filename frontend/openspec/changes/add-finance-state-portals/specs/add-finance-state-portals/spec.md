# add-finance-state-portals Specification

## Purpose

Mostrar estado de cuenta comprensible a familias y reportes a Yanina.

## ADDED Requirements

### Requirement 1

Portal SHALL mostrar ambos montos antes del límite

#### Scenario: ve 450 hasta fecha y 480 después

- GIVEN deuda es elegible
- WHEN familia consulta
- THEN ve 450 hasta fecha y 480 después

### Requirement 2

Padre SHALL ver solo hijos vinculados

#### Scenario: muestra únicamente el hijo seleccionado

- GIVEN cambia contexto
- WHEN consulta estado
- THEN muestra únicamente el hijo seleccionado

