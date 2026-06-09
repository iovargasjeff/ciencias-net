# Proposal: add-student-attendance-supervision

**ID:** FE-010  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Kiara  
**Reviewer:** Jefferson
**Dependencias:** FE-002, FE-003, Backend BE-011

## Why

Permitir al Auxiliar supervisar jornadas y resolver excepciones.

## In Scope

- tablero de eventos y anomalías
- revisión dudosa y corrección
- salida de emergencia, falta y justificación

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: tablero de eventos y anomalías, revisión dudosa y corrección, salida de emergencia, falta y justificación.

## API Contract

- Declaracion contractual: consultar la fila `add-student-attendance-supervision` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/attendance.md`
- `../../../../docs/product/roles-and-permissions.md`
