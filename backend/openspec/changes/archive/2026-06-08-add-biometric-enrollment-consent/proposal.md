# Proposal: add-biometric-enrollment-consent

**ID:** BE-007  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Jefferson  
**Reviewer:** Jefferson
**Dependencias:** DB-002

## Why

Enrolar rostros únicamente con consentimiento y almacenamiento privado.

## In Scope

- otorgar/revocar consentimiento
- enrolamiento de 3 a 5 fotos
- perfil activo, eliminación programada y R2 privado

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: otorgar/revocar consentimiento, enrolamiento de 3 a 5 fotos, perfil activo, eliminación programada y R2 privado.

## API Contract

- Declaracion contractual: consultar la fila `add-biometric-enrollment-consent` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/security/data-and-files.md`
- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/database-schema.md`
