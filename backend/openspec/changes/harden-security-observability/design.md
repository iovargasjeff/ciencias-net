# Design: harden-security-observability

## Sources and Invariants

- `../../../../docs/security/overview.md`
- `../../../../docs/security/audit-and-operations.md`
- `../../../../docs/architecture/deployment.md`

## Technical Design

- Configurar controles HTTP y rate limits.
- Instrumentar auditoría y correlación.
- Agregar métricas/alertas.
- Revisar redacción de datos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- escaneo de logs sin sensibles.
- rate limits probados.
- eventos críticos auditados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
