# add-academic-structure-administration Specification

## Purpose

Administrar periodos, grados, secciones, matrículas, cursos y cargas.

## ADDED Requirements

### Requirement 1

Coordinación SHALL gestionar estructura desde panel

#### Scenario: listas se actualizan

- GIVEN abre módulo académico
- WHEN crea entidades válidas
- THEN listas se actualizan

### Requirement 2

Cambio de docente SHALL mostrar vigencia

#### Scenario: histórico permanece visible

- GIVEN coordinación reasigna carga
- WHEN confirma
- THEN histórico permanece visible

