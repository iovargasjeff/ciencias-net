# define-api-contract-conventions Specification

## Purpose

Fijar un contrato HTTP estable para todos los módulos y para frontend.

## ADDED Requirements

### Requirement 1

La API SHALL responder errores con códigos y estructura estables

#### Scenario: frontend recibe código, mensaje y campos

- GIVEN una solicitud es inválida
- WHEN Laravel la procesa
- THEN frontend recibe código, mensaje y campos

### Requirement 2

Los listados SHALL ser paginados

#### Scenario: responde datos y metadatos de página

- GIVEN existen más registros que el límite
- WHEN se consulta el listado
- THEN responde datos y metadatos de página

