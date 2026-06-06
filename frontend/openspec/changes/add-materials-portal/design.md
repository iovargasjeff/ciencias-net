# Design: add-materials-portal

## Sources and Invariants

- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/security/data-and-files.md`

## Technical Design

- Crear páginas de gestión y portal.
- Crear upload/enlace y progreso.
- Implementar filtros y descarga autorizada.
- Cubrir estados.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- alcance de matrícula probado.
- upload inválido visible.
- descarga privada E2E.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
