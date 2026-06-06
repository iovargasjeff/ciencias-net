# add-result-publication-ranking-reports Specification

## Purpose

Publicar resultados, calcular ranking y producir reportes protegidos.

## ADDED Requirements

### Requirement 1

Resultados SHALL ser visibles solo publicados o cerrados

#### Scenario: no recibe notas

- GIVEN evaluación sigue borrador
- WHEN alumno consulta
- THEN no recibe notas

### Requirement 2

Empates SHALL compartir posición

#### Scenario: ambos reciben mismo puesto

- GIVEN dos alumnos tienen mismo puntaje
- WHEN se publica
- THEN ambos reciben mismo puesto

