# Proposal: add-teacher-attendance-payroll

**ID:** FE-011  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Kiara  
**Reviewer:** Jefferson
**Dependencias:** FE-003, Backend BE-012/BE-013

## Why

Permitir a Yanina revisar asistencia docente y cerrar planilla.

## In Scope

- asistencia, clases canceladas y sustitutos
- tarifas y descuentos
- revisión/cierre y reporte mensual

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: asistencia, clases canceladas y sustitutos, tarifas y descuentos, revisión/cierre y reporte mensual.

## API Contract

- Declaracion contractual: consultar la fila `add-teacher-attendance-payroll` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
