# Proposal: add-biometric-station-administration

**ID:** FE-009A  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Kiara  
**Reviewer:** Jefferson
**Dependencias:** FE-003, Backend BE-007/BE-009

## Why

Administrar consentimiento, enrolamiento y dispositivos sin acceder a datos biométricos crudos.

## In Scope

- otorgar/revocar consentimiento y enrolar con 3 a 5 fotos
- listar/crear/revocar estaciones y cámaras
- generar activación temporal y revisar estado

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: otorgar/revocar consentimiento y enrolar con 3 a 5 fotos, listar/crear/revocar estaciones y cámaras, generar activación temporal y revisar estado.

## API Contract

- Declaracion contractual: consultar la fila `add-biometric-station-administration` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/data-and-files.md`
- `../../../../docs/product/roles-and-permissions.md`
