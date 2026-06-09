# Proposal: add-web-station-activation-capture

**ID:** FE-009  
**Fase:** Fase 2: Facial y asistencia  
**Owner:** Vincenzo  
**Reviewer:** Jefferson
**Dependencias:** FE-003, Backend BE-009/BE-010

## Why

Operar estaciones web limitadas en celular, tablet o PC multicámara.

## In Scope

- activar con QR/código
- seleccionar cámaras y modo
- captura, resultado, timeout y estado revocado

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: activar con QR/código, seleccionar cámaras y modo, captura, resultado, timeout y estado revocado.

## API Contract

- Declaracion contractual: consultar la fila `add-web-station-activation-capture` de [`../../API_CONTRACTS.md`](../../API_CONTRACTS.md).
- Aplicar la relacion indicada antes de implementar; si declara `Sin contrato HTTP`, no inventar endpoints.

## Source Documents

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/decisions/002-web-attendance-stations.md`
