# add-web-station-management Specification

## Purpose

Activar y revocar navegadores de asistencia sin compartir sesiones humanas.

## ADDED Requirements

### Requirement 1

Una activación SHALL ser de un solo uso y expirar

#### Scenario: Laravel rechaza

- GIVEN el código fue usado o venció
- WHEN se intenta activar
- THEN Laravel rechaza

### Requirement 2

Una estación SHALL acceder solo a captura y estado mínimo

#### Scenario: Policy rechaza

- GIVEN sesión técnica intenta consultar notas
- WHEN llama endpoint humano
- THEN Policy rechaza

