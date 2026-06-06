# Proposal: add-family-links-management

**ID:** BE-005  
**Fase:** Fase 1: Identidad y estructura académica  
**Owner:** Fátima  
**Reviewer:** Jefferson  
**Dependencias:** BE-004

## Why

Gestionar relaciones N:M entre padres y alumnos con alcance seguro.

## In Scope

- crear perfiles padre/alumno
- vincular y desvincular familiares
- consultar hijos vinculados y contextos

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: crear perfiles padre/alumno, vincular y desvincular familiares, consultar hijos vinculados y contextos.

## Source Documents

- `../../../../docs/domain/identity-access.md`
- `../../../../docs/product/roles-and-permissions.md`
- `../../../../docs/architecture/database-schema.md`
