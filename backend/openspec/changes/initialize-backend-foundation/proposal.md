# Proposal: initialize-backend-foundation

**ID:** BE-001  
**Fase:** Fase 0: Fundación ejecutable  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** Ninguna

## Why

Entregar una API Laravel clonable y verificable antes de implementar dominios.

## In Scope

- Laravel 13, PHP 8.3 y PostgreSQL
- Sanctum, Spatie Permission, Pest y Scribe
- estructura modular, configuración y healthcheck

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: Laravel 13, PHP 8.3 y PostgreSQL, Sanctum, Spatie Permission, Pest y Scribe, estructura modular, configuración y healthcheck.

## Source Documents

- `../../../../docs/architecture/backend.md`
- `../../../../docs/decisions/005-technical-foundation.md`
