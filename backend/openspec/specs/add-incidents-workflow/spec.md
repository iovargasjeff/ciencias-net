# add-incidents-workflow Specification

## Purpose

Implementar el cuaderno de incidencias desde Auxiliar hasta TOE.

## ADDED Requirements

### Requirement: 1

Auxiliar SHALL registrar y derivar a TOE

#### Scenario: TOE puede continuarlo

- GIVEN auxiliar documenta caso
- WHEN deriva
- THEN TOE puede continuarlo

### Requirement: 2

TOE SHALL notificar al padre vinculado

#### Scenario: se crea notificación y correo

- GIVEN caso grave requiere aviso
- WHEN TOE confirma
- THEN se crea notificación y correo

