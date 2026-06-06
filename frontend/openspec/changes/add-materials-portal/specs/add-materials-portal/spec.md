# add-materials-portal Specification

## Purpose

Publicar y consultar materiales según carga/matrícula.

## ADDED Requirements

### Requirement 1

Alumno SHALL ver materiales de su matrícula

#### Scenario: solo aparecen permitidos

- GIVEN abre materiales
- WHEN filtra semana
- THEN solo aparecen permitidos

### Requirement 2

Upload SHALL mostrar validaciones

#### Scenario: UI muestra error sin perder contexto

- GIVEN archivo excede reglas
- WHEN docente intenta subir
- THEN UI muestra error sin perder contexto

