# Proposal: add-assessment-management

**ID:** BE-018  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** Jefferson  
**Reviewer:** André
**Dependencias:** BE-006, DB-004

## Why

Configurar evaluaciones físicas y su ciclo de revisión.

## In Scope

- crear examen por carga/grado/canal
- puntaje máximo y estados
- cerrar y reabrir auditadamente

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: crear examen por carga/grado/canal, puntaje máximo y estados, cerrar y reabrir auditadamente.

## API Contract

- Declaracion contractual: consultar la fila `add-assessment-management` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
