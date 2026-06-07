# add-evaluation-content-schema Specification

## Purpose

Persistir evaluaciones, resultados, reportes, materiales, horarios y comunicación.

## ADDED Requirements

### Requirement: 1

Una nota SHALL pertenecer a matrícula y examen compatibles

#### Scenario: constraint o caso de uso rechaza

- GIVEN se intenta nota ajena a sección
- WHEN se guarda
- THEN constraint o caso de uso rechaza

### Requirement: 2

Una lectura SHALL ser única por comunicado y usuario

#### Scenario: queda un registro

- GIVEN usuario marca dos veces
- WHEN se persiste
- THEN queda un registro

