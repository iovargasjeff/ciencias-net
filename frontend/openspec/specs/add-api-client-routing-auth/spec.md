# add-api-client-routing-auth Specification

## Purpose

Conectar la SPA con Sanctum y separar contextos humanos y técnicos.

## Requirements

### Requirement 1

La SPA SHALL autenticarse sin guardar tokens en localStorage.

#### Scenario: la sesión usa cookie

- GIVEN usuario inicia sesión
- WHEN frontend obtiene CSRF y autentica
- THEN la sesión usa cookie

### Requirement 2

Una estación SHALL no navegar al portal humano.

#### Scenario: queda bloqueada

- GIVEN sesión técnica cambia URL
- WHEN router y API validan
- THEN queda bloqueada
