# Proposal: add-biometric-enrollment-consent

**ID:** BE-007  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Jefferson  
**Reviewer:** Fátima  
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

## Source Documents

- `../../../../docs/security/data-and-files.md`
- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/database-schema.md`
