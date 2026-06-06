# add-human-authentication Specification

## Purpose

Permitir acceso humano seguro sin autorregistro.

## ADDED Requirements

### Requirement 1

El sistema SHALL autenticar la SPA mediante cookie y CSRF

#### Scenario: recibe sesión sin token en localStorage

- GIVEN una cuenta activa usa credenciales válidas
- WHEN inicia sesión
- THEN recibe sesión sin token en localStorage

### Requirement 2

Una cuenta desactivada SHALL perder acceso

#### Scenario: recibe rechazo genérico

- GIVEN una cuenta fue desactivada
- WHEN intenta usar la API
- THEN recibe rechazo genérico

