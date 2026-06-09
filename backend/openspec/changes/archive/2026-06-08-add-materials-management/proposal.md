# Proposal: add-materials-management

**ID:** BE-021  
**Fase:** Fase 4: Evaluación y contenido  
**Owner:** André  
**Reviewer:** André
**Dependencias:** BE-006, DB-004

## Why

Publicar recursos académicos privados para matrículas autorizadas.

## In Scope

- subir archivo o enlace
- editar, reemplazar y eliminar
- listar/descargar por carga y matrícula

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: subir archivo o enlace, editar, reemplazar y eliminar, listar/descargar por carga y matrícula.

## API Contract

- Declaracion contractual: consultar la fila `add-materials-management` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/data-and-files.md`
- `../../../../docs/domain/academic.md`
