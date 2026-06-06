# Proposal: add-api-client-routing-auth

**ID:** FE-003  
**Fase:** Fase 0: Fundación ejecutable  
**Owner:** Vincenzo  
**Reviewer:** Kiara  
**Dependencias:** FE-001, Backend BE-002/BE-003

## Why

Conectar la SPA con Sanctum y separar contextos humanos y técnicos.

## In Scope

- Axios CSRF/withCredentials y TanStack Query
- login, recuperación, selección de contexto y logout
- rutas públicas, portal, admin y estación
- ProtectedRoute, PermissionRoute y manejo 401/403/419

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: Axios CSRF/withCredentials y TanStack Query, login, recuperación, selección de contexto y logout, rutas públicas, portal, admin y estación, ProtectedRoute, PermissionRoute y manejo 401/403/419.

## Source Documents

- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/architecture/frontend.md`
- `../../../../docs/architecture/facial-integration.md`
