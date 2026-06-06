# Proposal: add-web-station-management

**ID:** BE-009  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** André  
**Reviewer:** Jefferson  
**Dependencias:** BE-004, DB-002, BE-008

## Why

Activar y revocar navegadores de asistencia sin compartir sesiones humanas.

## In Scope

- crear estación/cámaras
- QR o código de un uso por 10 minutos
- sesión técnica limitada, rotación y revocación

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: crear estación/cámaras, QR o código de un uso por 10 minutos, sesión técnica limitada, rotación y revocación.

## Source Documents

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/decisions/002-web-attendance-stations.md`
