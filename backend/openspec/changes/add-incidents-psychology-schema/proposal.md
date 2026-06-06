# Proposal: add-incidents-psychology-schema

**ID:** DB-005  
**Fase:** Fase 5: Incidencias y Psicología  
**Owner:** Fátima  
**Reviewer:** Jefferson  
**Dependencias:** DB-001

## Why

Persistir incidencias, historial y atenciones privadas con separación clara.

## In Scope

- incidencias e historial inmutable
- atenciones psicológicas privadas
- archivos y estados de derivación

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: incidencias e historial inmutable, atenciones psicológicas privadas, archivos y estados de derivación.

## Source Documents

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/data-and-files.md`
