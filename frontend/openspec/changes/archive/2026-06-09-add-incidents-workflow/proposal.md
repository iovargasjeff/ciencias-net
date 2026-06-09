# Proposal: add-incidents-workflow

**ID:** FE-020  
**Fase:** Fase 5: Incidencias y Psicología  
**Owner:** Kiara  
**Reviewer:** Jefferson
**Dependencias:** FE-003, Backend BE-024

## Why

Operar el cuaderno de incidencias y seguimiento TOE.

## In Scope

- registro Auxiliar/TOE
- derivación, comentarios, notificación y resolución
- historial y reporte

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: registro Auxiliar/TOE, derivación, comentarios, notificación y resolución, historial y reporte.

## API Contract

- Declaracion contractual: consultar la fila `add-incidents-workflow` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/product/roles-and-permissions.md`
