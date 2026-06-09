# Proposal: add-finance-queries-reminders

**ID:** BE-017  
**Fase:** Fase 3: Finanzas  
**Owner:** André  
**Reviewer:** Jefferson  
**Dependencias:** BE-016

## Why

Entregar estados de cuenta, morosidad, caja y recordatorios con alcance correcto.

## In Scope

- estado de cuenta de alumno/padre
- morosos y reporte de caja
- recordatorios por panel y correo

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: estado de cuenta de alumno/padre, morosos y reporte de caja, recordatorios por panel y correo.

## API Contract

- Declaracion contractual: consultar la fila `add-finance-queries-reminders` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/product/roles-and-permissions.md`
