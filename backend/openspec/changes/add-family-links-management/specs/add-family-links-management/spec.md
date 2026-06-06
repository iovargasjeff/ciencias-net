# add-family-links-management Specification

## Purpose

Gestionar relaciones N:M entre padres y alumnos con alcance seguro.

## ADDED Requirements

### Requirement 1

Un padre SHALL poder vincularse con varios alumnos

#### Scenario: todos quedan disponibles al padre

- GIVEN el gestor tiene perfiles válidos
- WHEN crea vínculos
- THEN todos quedan disponibles al padre

### Requirement 2

Un padre SHALL consultar solo hijos vinculados

#### Scenario: la Policy responde 404 o 403 sin filtrar datos

- GIVEN solicita un alumno ajeno
- WHEN consulta el recurso
- THEN la Policy responde 404 o 403 sin filtrar datos

