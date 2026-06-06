# Proposal: add-student-attendance-closure-review

**ID:** BE-011  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Jefferson  
**Reviewer:** Fátima  
**Dependencias:** BE-010

## Why

Cerrar jornadas y resolver excepciones sin inventar movimientos.

## In Scope

- generar faltas al cierre completo
- anomalía de ingreso sin salida
- revisión dudosa, corrección y justificación
- alerta por faltas injustificadas

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: generar faltas al cierre completo, anomalía de ingreso sin salida, revisión dudosa, corrección y justificación, alerta por faltas injustificadas.

## Source Documents

- `../../../../docs/domain/attendance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/audit-and-operations.md`
