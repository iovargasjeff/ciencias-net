# add-facial-service-integration Specification

## Purpose

Integrar Laravel con Python sin delegar reglas de asistencia.

## ADDED Requirements

### Requirement: 1

Python SHALL devolver identidad, confianza y prueba de vida sin registrar asistencia

#### Scenario: devuelve resultado mínimo

- GIVEN recibe captura autenticada
- WHEN procesa reconocimiento
- THEN devuelve resultado mínimo

### Requirement: 2

Laravel SHALL tratar timeout como evento no automático

#### Scenario: ofrece revisión o alternativa manual

- GIVEN Python excede 5 segundos
- WHEN Laravel espera respuesta
- THEN ofrece revisión o alternativa manual

