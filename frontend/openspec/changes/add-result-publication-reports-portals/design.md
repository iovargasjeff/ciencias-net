# Design: add-result-publication-reports-portals

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear panel publicación/cierre.
- Crear diálogo corrección auditada.
- Crear portales de resultados/ranking.
- Integrar descarga de reportes.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- visibilidad por estado pasa.
- alcance familiar E2E.
- confirmaciones accesibles.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
