# add-biometric-station-administration Specification

## Purpose

Administrar consentimiento, enrolamiento y dispositivos sin acceder a datos biométricos crudos.

## ADDED Requirements

### Requirement 1

Gestor autorizado SHALL enrolar solo con consentimiento

#### Scenario: UI y API lo bloquean

- GIVEN persona no consintió
- WHEN intenta iniciar enrolamiento
- THEN UI y API lo bloquean

### Requirement 2

Gestor SHALL revocar una estación comprometida

#### Scenario: queda inactiva y visible en historial

- GIVEN selecciona estación activa
- WHEN confirma revocación
- THEN queda inactiva y visible en historial

