# Proposal: add-result-publication-ranking-reports

**ID:** BE-020  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** Jefferson  
**Reviewer:** André
**Dependencias:** BE-019

## Why

Publicar resultados, calcular ranking y producir reportes protegidos.

## In Scope

- publicar y recalcular ranking
- corregir publicado y renotificar
- libreta/reporte y consultas alumno/padre

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: publicar y recalcular ranking, corregir publicado y renotificar, libreta/reporte y consultas alumno/padre.

## API Contract

- Declaracion contractual: consultar la fila `add-result-publication-ranking-reports` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/product/roles-and-permissions.md`
