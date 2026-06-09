# Proposal: add-communications-notifications

**ID:** BE-023  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** André  
**Reviewer:** André
**Dependencias:** BE-004, DB-004

## Why

Publicar comunicaciones segmentadas y registrar su entrega/lectura.

## In Scope

- comunicado por rol, periodo, grado o sección
- notificación de panel y correo
- lectura y archivo

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: comunicado por rol, periodo, grado o sección, notificación de panel y correo, lectura y archivo.

## API Contract

- Declaracion contractual: consultar la fila `add-communications-notifications` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`
