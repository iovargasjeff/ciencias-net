# add-result-publication-reports-portals Specification

## Purpose

Publicar, corregir y consultar resultados/rankings protegidos.

## ADDED Requirements

### Requirement 1

Notas SHALL ocultarse antes de publicación

#### Scenario: no ve resultados

- GIVEN evaluación está borrador
- WHEN familia consulta
- THEN no ve resultados

### Requirement 2

Corrección publicada SHALL advertir recálculo/notificación

#### Scenario: ve impacto antes de confirmar

- GIVEN coordinación corrige
- WHEN abre diálogo
- THEN ve impacto antes de confirmar

