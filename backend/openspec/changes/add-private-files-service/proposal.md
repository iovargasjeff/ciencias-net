# Proposal: add-private-files-service

**ID:** BE-026  
**Fase:** Fase 6: Operación y release  
**Owner:** André  
**Reviewer:** Fátima  
**Dependencias:** BE-001

## Why

Centralizar archivos privados y evitar storage público accidental.

## In Scope

- uploads privados y descargas autorizadas
- URLs firmadas cortas para R2
- validación, retención y eliminación

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: uploads privados y descargas autorizadas, URLs firmadas cortas para R2, validación, retención y eliminación.

## Source Documents

- `../../../../docs/security/data-and-files.md`
- `../../../../docs/architecture/deployment.md`
