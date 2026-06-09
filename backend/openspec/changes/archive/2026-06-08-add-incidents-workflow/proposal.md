# Proposal: add-incidents-workflow

**ID:** BE-024  
**Fase:** Fase 5: Incidencias y Psicología  
**Owner:** Jefferson  
**Reviewer:** André
**Dependencias:** BE-004, DB-005

## Why

Implementar el cuaderno de incidencias desde Auxiliar hasta TOE.

## In Scope

- registrar, comentar y derivar a TOE
- notificar padre y resolver
- historial y reporte semanal

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: registrar, comentar y derivar a TOE, notificar padre y resolver, historial y reporte semanal.

## API Contract

- Declaracion contractual: consultar la fila `add-incidents-workflow` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/product/roles-and-permissions.md`
