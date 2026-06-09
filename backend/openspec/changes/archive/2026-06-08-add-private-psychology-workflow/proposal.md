# Proposal: add-private-psychology-workflow

**ID:** BE-025  
**Fase:** Fase 5: Incidencias y Psicología  
**Owner:** Jefferson  
**Reviewer:** André
**Dependencias:** BE-024

## Why

Registrar atención psicológica sin exponer notas privadas.

## In Scope

- derivación TOE a Psicología
- atención y notas confidenciales
- consulta exclusiva Psicología/superadmin

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: derivación TOE a Psicología, atención y notas confidenciales, consulta exclusiva Psicología/superadmin.

## API Contract

- Declaracion contractual: consultar la fila `add-private-psychology-workflow` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/security/audit-and-operations.md`
