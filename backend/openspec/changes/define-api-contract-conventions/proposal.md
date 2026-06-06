# Proposal: define-api-contract-conventions

**ID:** BE-002  
**Fase:** Fase 0: Fundación ejecutable  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** BE-001

## Why

Fijar un contrato HTTP estable para todos los módulos y para frontend.

## In Scope

- /api/v1, errores, validación y paginación
- UUID, fechas, filtros e idempotencia
- OpenAPI generado con Scribe

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: /api/v1, errores, validación y paginación, UUID, fechas, filtros e idempotencia, OpenAPI generado con Scribe.

## Source Documents

- `../../../../docs/architecture/backend.md`
- `../../../../docs/security/overview.md`
