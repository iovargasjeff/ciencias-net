# Proposal: add-human-authentication

**ID:** BE-003  
**Fase:** Fase 1: Identidad y estructura académica  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** BE-002, DB-001

## Why

Permitir acceso humano seguro sin autorregistro.

## In Scope

- login/logout Sanctum SPA
- recuperación por correo
- sesión, contextos y expiración

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: login/logout Sanctum SPA, recuperación por correo, sesión, contextos y expiración.

## Source Documents

- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/domain/identity-access.md`
