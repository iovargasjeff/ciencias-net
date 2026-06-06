# add-schedules-calendar-portals Specification

## Purpose

Administrar y consultar horarios/calendario por contexto.

## ADDED Requirements

### Requirement 1

Cada actor SHALL ver su horario permitido

#### Scenario: recibe vista de su contexto

- GIVEN usuario abre horario
- WHEN consulta
- THEN recibe vista de su contexto

### Requirement 2

Editor SHALL advertir solapamientos

#### Scenario: muestra error

- GIVEN coordinación crea conflicto
- WHEN intenta guardar
- THEN muestra error

