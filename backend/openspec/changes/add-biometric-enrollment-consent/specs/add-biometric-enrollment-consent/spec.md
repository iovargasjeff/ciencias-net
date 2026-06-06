# add-biometric-enrollment-consent Specification

## Purpose

Enrolar rostros únicamente con consentimiento y almacenamiento privado.

## ADDED Requirements

### Requirement 1

El sistema SHALL impedir enrolar sin consentimiento otorgado

#### Scenario: la operación se rechaza

- GIVEN una persona no consintió
- WHEN se intenta enrolar
- THEN la operación se rechaza

### Requirement 2

Revocar consentimiento SHALL desactivar el perfil

#### Scenario: queda inactivo y se programa eliminación

- GIVEN existe perfil activo
- WHEN se revoca consentimiento
- THEN queda inactivo y se programa eliminación

