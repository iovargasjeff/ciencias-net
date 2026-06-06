# Design: configure-backend-quality-ci

## Sources and Invariants

- `../../../../docs/architecture/backend.md`
- `../../../../docs/architecture/deployment.md`

## Technical Design

- Configurar checks PHP y Pest.
- Añadir PostgreSQL de CI y smoke de migraciones.
- Verificar Scribe y secretos.
- Documentar equivalentes locales.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- pipeline pasa desde clon.
- fallo intencional bloquea.
- no aparecen secretos.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
