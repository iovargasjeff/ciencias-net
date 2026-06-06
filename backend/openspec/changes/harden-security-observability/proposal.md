# Proposal: harden-security-observability

**ID:** BE-027  
**Fase:** Fase 6: Operación y release  
**Owner:** André  
**Reviewer:** Fátima  
**Dependencias:** BE-003, BE-026

## Why

Aplicar controles transversales, auditoría y observabilidad antes de producción.

## In Scope

- rate limits, headers y reautenticación
- audit logs y correlación
- métricas y alertas sin datos sensibles

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: rate limits, headers y reautenticación, audit logs y correlación, métricas y alertas sin datos sensibles.

## Source Documents

- `../../../../docs/security/overview.md`
- `../../../../docs/security/audit-and-operations.md`
- `../../../../docs/architecture/deployment.md`
