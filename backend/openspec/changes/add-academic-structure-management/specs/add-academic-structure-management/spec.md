# add-academic-structure-management Specification

## Purpose

Administrar la estructura que gobierna horarios, notas y materiales.

## ADDED Requirements

### Requirement 1

Coordinación SHALL gestionar estructura académica

#### Scenario: queda disponible para cargas

- GIVEN coordinador autorizado envía datos válidos
- WHEN crea la estructura
- THEN queda disponible para cargas

### Requirement 2

Cambiar docente SHALL conservar cargas históricas

#### Scenario: la anterior conserva vigencia histórica

- GIVEN una carga vigente cambia
- WHEN se asigna nuevo docente
- THEN la anterior conserva vigencia histórica

