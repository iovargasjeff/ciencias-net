# add-result-entry-import Specification

## Purpose

Registrar resultados procesados manualmente sin que el sistema tome exámenes.

## ADDED Requirements

### Requirement: 1

Un docente SHALL registrar solo notas de su carga activa

#### Scenario: Policy rechaza

- GIVEN docente usa carga ajena
- WHEN envía nota
- THEN Policy rechaza

### Requirement: 2

La importación SHALL ser atómica

#### Scenario: no se guarda ninguna fila

- GIVEN una fila masiva es inválida
- WHEN se confirma importación
- THEN no se guarda ninguna fila

