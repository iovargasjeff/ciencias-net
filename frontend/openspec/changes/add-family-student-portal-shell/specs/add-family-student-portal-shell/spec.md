# add-family-student-portal-shell Specification

## Purpose

Dar a padres y alumnos un portal de consulta con contexto seguro.

## ADDED Requirements

### Requirement 1

Un padre SHALL cambiar entre hijos vinculados

#### Scenario: panel muestra solo ese hijo

- GIVEN padre tiene varios hijos
- WHEN elige otro contexto
- THEN panel muestra solo ese hijo

### Requirement 2

Alumno SHALL ver únicamente contexto propio

#### Scenario: no puede cambiar a otro alumno

- GIVEN alumno navega portal
- WHEN consulta módulos
- THEN no puede cambiar a otro alumno

### Requirement 3

Familia SHALL consultar solo estado biométrico

#### Scenario: ve activo/inactivo sin fotos ni embedding

- GIVEN padre abre estado de hijo
- WHEN consulta
- THEN ve activo/inactivo sin fotos ni embedding

