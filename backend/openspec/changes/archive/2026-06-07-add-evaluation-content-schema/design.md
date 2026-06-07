# Design: add-evaluation-content-schema

## Sources and Invariants

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/incidents-communications.md`

## Technical Design

- Crear migraciones de evaluación/contenido.
- Agregar estados, checks e índices.
- Crear factories representativas.
- Probar rollback.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- constraints académicos pasan.
- lectura idempotente.
- índices verificados.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
