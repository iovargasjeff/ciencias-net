# Design: initialize-docker-development

## Sources and Invariants

- `../../../../docs/architecture/deployment.md`
- `../../../../docs/architecture/deployment-runbook.md`
- `../../../../docs/architecture/detailed-system-design.md`

## Technical Design

- Crear Dockerfiles y Compose.
- Configurar healthchecks, redes y volúmenes.
- Preparar facial-service FastAPI mínimo.
- Documentar operación y diagnóstico.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- docker compose config válido.
- todos los healthchecks pasan.
- reinicio conserva PostgreSQL.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
