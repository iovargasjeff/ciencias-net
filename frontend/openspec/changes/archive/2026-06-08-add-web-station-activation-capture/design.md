# Design: add-web-station-activation-capture

## Sources and Invariants

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/decisions/002-web-attendance-stations.md`

## Technical Design

- Crear rutas StationLayout.
- Implementar activación y permisos de cámara.
- Crear captura e idempotency key.
- Mostrar éxito, revisión, rechazo, timeout y revocación.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- E2E activación un uso.
- multicámara probado.
- retroceso no abre portal.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
