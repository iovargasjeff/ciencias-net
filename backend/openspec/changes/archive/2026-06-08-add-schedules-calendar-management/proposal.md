# Proposal: add-schedules-calendar-management

**ID:** BE-022  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** André  
**Reviewer:** André
**Dependencias:** BE-006, DB-004

## Why

Administrar horarios, calendario y días no laborables.

## In Scope

- horario semanal por sección/docente
- eventos de calendario
- días no laborables y consultas familiares

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: horario semanal por sección/docente, eventos de calendario, días no laborables y consultas familiares.

## API Contract

- Declaracion contractual: consultar la fila `add-schedules-calendar-management` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`
