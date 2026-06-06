# Design: add-web-station-management

## Sources and Invariants

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/authentication-authorization.md`
- `../../../../docs/decisions/002-web-attendance-stations.md`

## Technical Design

- Crear casos de uso de estaciones y cámaras.
- Emitir activación y cookie técnica.
- Restringir abilities y rutas.
- Auditar rotación/revocación.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- código usado dos veces falla.
- estación revocada pierde acceso.
- rutas humanas bloqueadas.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
