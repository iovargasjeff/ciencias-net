# Proposal: configure-backend-quality-ci

**ID:** OPS-002  
**Fase:** Fase 0: Fundación ejecutable  
**Owner:** André  
**Reviewer:** Jefferson  
**Dependencias:** BE-001, OPS-001

## Why

Impedir integrar backend roto o sin contrato actualizado.

## In Scope

- formato, análisis y Pest
- smoke de migraciones y OpenAPI
- pipeline reproducible

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: formato, análisis y Pest, smoke de migraciones y OpenAPI, pipeline reproducible.

## Source Documents

- `../../../../docs/architecture/backend.md`
- `../../../../docs/architecture/deployment.md`
