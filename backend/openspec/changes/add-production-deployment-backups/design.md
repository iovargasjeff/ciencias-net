# Design: add-production-deployment-backups

## Sources and Invariants

- `../../../../docs/architecture/deployment.md`
- `../../../../docs/architecture/deployment-runbook.md`

## Technical Design

- Crear configuración producción y secretos externos.
- Automatizar backup integral y checksums.
- Documentar restore y rollback.
- Probar restauración aislada.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- puertos privados verificados.
- restore trimestral documentado.
- alerta ante backup fallido.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
