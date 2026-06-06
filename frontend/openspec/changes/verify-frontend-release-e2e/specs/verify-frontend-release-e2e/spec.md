# verify-frontend-release-e2e Specification

## Purpose

Demostrar los flujos completos de todos los roles antes de liberar.

## ADDED Requirements

### Requirement 1

Release SHALL cubrir flujos críticos de cada rol

#### Scenario: todos los recorridos pasan

- GIVEN entorno release está activo
- WHEN se ejecuta E2E
- THEN todos los recorridos pasan

### Requirement 2

Release SHALL no contener rutas o mocks temporales

#### Scenario: usa contratos publicados reales

- GIVEN se inspecciona build
- WHEN se navega
- THEN usa contratos publicados reales

