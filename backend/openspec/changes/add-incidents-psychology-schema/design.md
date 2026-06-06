# Design: add-incidents-psychology-schema

## Sources and Invariants

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/data-and-files.md`

## Technical Design

- Crear migraciones e índices.
- Separar campos privados.
- Agregar FKs y estados.
- Probar rollback y privacidad.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- historial no se elimina.
- queries generales excluyen privado.
- rollback completo.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
