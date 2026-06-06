# Proposal: add-student-attendance-events

**ID:** BE-010  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** BE-008, BE-009

## Why

Convertir capturas válidas en entradas, salidas y reingresos de alumnos.

## In Scope

- idempotencia y modo de cámara
- alternancia bidireccional
- tardanza, salidas de emergencia y notificaciones

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: idempotencia y modo de cámara, alternancia bidireccional, tardanza, salidas de emergencia y notificaciones.

## Source Documents

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/facial-integration.md`
