# Design: add-assessment-management

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear entidad y casos de uso de evaluación.
- Validar canal y puntaje.
- Aplicar Policies de coordinación/docente.
- Auditar cierre/reapertura.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- canales y estados probados.
- docente no crea fuera de carga.
- cerrada bloquea edición.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
