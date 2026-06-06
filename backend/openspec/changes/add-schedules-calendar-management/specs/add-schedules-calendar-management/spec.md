# add-schedules-calendar-management Specification

## Purpose

Administrar horarios, calendario y días no laborables.

## ADDED Requirements

### Requirement 1

Coordinación SHALL gestionar horarios

#### Scenario: queda consultable por actores vinculados

- GIVEN coordinador guarda horario válido
- WHEN confirma
- THEN queda consultable por actores vinculados

### Requirement 2

Un padre SHALL ver horario del hijo vinculado

#### Scenario: se rechaza

- GIVEN consulta hijo ajeno
- WHEN envía solicitud
- THEN se rechaza

