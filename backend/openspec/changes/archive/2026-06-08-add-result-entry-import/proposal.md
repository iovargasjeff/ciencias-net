# Proposal: add-result-entry-import

**ID:** BE-019  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** Jefferson  
**Reviewer:** André
**Dependencias:** BE-018

## Why

Registrar resultados procesados manualmente sin que el sistema tome exámenes.

## In Scope

- registro individual docente
- previsualización e importación masiva atómica
- estados ausente, exonerado y pendiente

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: registro individual docente, previsualización e importación masiva atómica, estados ausente, exonerado y pendiente.

## API Contract

- Declaracion contractual: consultar la fila `add-result-entry-import` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
