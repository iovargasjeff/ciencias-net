# Proposal: add-private-psychology-portal

**ID:** FE-021  
**Fase:** Fase 5: Incidencias y Psicología  
**Owner:** Kiara  
**Reviewer:** Jefferson
**Dependencias:** FE-003, Backend BE-025

## Why

Ofrecer un espacio explícitamente privado para Psicología y superadmin.

## In Scope

- bandeja de derivados
- registro y consulta de atención privada
- bloqueo total a otros roles

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: bandeja de derivados, registro y consulta de atención privada, bloqueo total a otros roles.

## API Contract

- Declaracion contractual: consultar la fila `add-private-psychology-portal` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/authentication-authorization.md`
