# add-incidents-psychology-schema Specification

## Purpose

Persistir incidencias, historial y atenciones privadas con separación clara.

## ADDED Requirements

### Requirement: 1

El historial SHALL conservar cada acción

#### Scenario: se agrega historial sin borrar anterior

- GIVEN una incidencia cambia estado
- WHEN se persiste
- THEN se agrega historial sin borrar anterior

### Requirement: 2

Notas privadas SHALL permanecer separadas de incidencia general

#### Scenario: no incluye notas psicológicas

- GIVEN TOE consulta incidencia
- WHEN recibe datos
- THEN no incluye notas psicológicas

