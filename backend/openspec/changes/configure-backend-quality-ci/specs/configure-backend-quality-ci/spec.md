# configure-backend-quality-ci Specification

## Purpose

Impedir integrar backend roto o sin contrato actualizado.

## ADDED Requirements

### Requirement 1

CI SHALL rechazar pruebas o migraciones fallidas

#### Scenario: el pipeline falla con evidencia

- GIVEN un cambio rompe una prueba
- WHEN se ejecuta pipeline
- THEN el pipeline falla con evidencia

### Requirement 2

CI SHALL comprobar el contrato publicado

#### Scenario: OpenAPI se regenera o el cambio falla

- GIVEN un endpoint cambia
- WHEN se ejecuta pipeline
- THEN OpenAPI se regenera o el cambio falla

