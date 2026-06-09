# Proposal: add-biometric-attendance-schema

**ID:** DB-002  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Fátima  
**Reviewer:** Jefferson  
**Dependencias:** DB-001

## Why

Persistir consentimiento, estaciones, reconocimientos y asistencia con trazabilidad.

## In Scope

- consentimientos, perfiles y archivos biométricos
- cuentas técnicas, estaciones, cámaras y activaciones
- eventos, movimientos, asistencias y anomalías

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: consentimientos, perfiles y archivos biométricos, cuentas técnicas, estaciones, cámaras y activaciones, eventos, movimientos, asistencias y anomalías.

## API Contract

- Declaracion contractual: consultar la fila `add-biometric-attendance-schema` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/data-and-files.md`
