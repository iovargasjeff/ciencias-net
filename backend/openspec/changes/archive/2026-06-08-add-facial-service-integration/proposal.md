# Proposal: add-facial-service-integration

**ID:** BE-008  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** André  
**Reviewer:** Jefferson  
**Dependencias:** OPS-001, DB-002

## Why

Integrar Laravel con Python sin delegar reglas de asistencia.

## In Scope

- FastAPI privado de salud, reconocimiento y sincronización
- calidad, prueba de vida y umbrales
- timeout, autenticación interna y perfiles en memoria

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: FastAPI privado de salud, reconocimiento y sincronización, calidad, prueba de vida y umbrales, timeout, autenticación interna y perfiles en memoria.

## API Contract

- Declaracion contractual: consultar la fila `add-facial-service-integration` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/detailed-system-design.md`
- `../../../../docs/security/authentication-authorization.md`
