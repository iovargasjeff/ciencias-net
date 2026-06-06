# add-assessment-result-entry Specification

## Purpose

Permitir configurar evaluaciones y cargar resultados procesados.

## ADDED Requirements

### Requirement 1

Docente SHALL cargar solo sus cursos

#### Scenario: solo aparecen asignadas

- GIVEN abre registro
- WHEN selecciona carga
- THEN solo aparecen asignadas

### Requirement 2

Importación SHALL mostrar errores antes de confirmar

#### Scenario: no permite confirmar hasta corregir

- GIVEN archivo contiene error
- WHEN se previsualiza
- THEN no permite confirmar hasta corregir

