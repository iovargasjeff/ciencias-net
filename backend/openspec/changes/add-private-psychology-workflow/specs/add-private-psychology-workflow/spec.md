# add-private-psychology-workflow Specification

## Purpose

Registrar atención psicológica sin exponer notas privadas.

## ADDED Requirements

### Requirement 1

Solo Psicología y superadmin SHALL consultar notas privadas

#### Scenario: se rechaza y no filtra contenido

- GIVEN TOE intenta consultar
- WHEN envía solicitud
- THEN se rechaza y no filtra contenido

### Requirement 2

Acceso sensible SHALL auditarse sin copiar notas

#### Scenario: audit log registra evento sin contenido

- GIVEN Psicología consulta atención
- WHEN se autoriza
- THEN audit log registra evento sin contenido

