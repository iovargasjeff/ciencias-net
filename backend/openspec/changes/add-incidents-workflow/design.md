# Design: add-incidents-workflow

## Sources and Invariants

- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear casos de uso e historial.
- Aplicar Policies por rol/vínculo.
- Integrar notificaciones.
- Crear reporte paginado.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- flujo Auxiliar-TOE pasa.
- padre ajeno no recibe.
- acciones quedan auditadas.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
