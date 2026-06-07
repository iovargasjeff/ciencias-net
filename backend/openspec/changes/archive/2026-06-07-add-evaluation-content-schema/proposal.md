# Proposal: add-evaluation-content-schema

**ID:** DB-004  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** Fátima  
**Reviewer:** André
**Dependencias:** DB-001

## Why

Persistir evaluaciones, resultados, reportes, materiales, horarios y comunicación.

## In Scope

- exámenes, notas y reportes académicos
- materiales, horarios y calendario
- comunicados, lecturas y notificaciones

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: exámenes, notas y reportes académicos, materiales, horarios y calendario, comunicados, lecturas y notificaciones.

## API Contract

- Declaracion contractual: consultar la fila `add-evaluation-content-schema` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/incidents-communications.md`
