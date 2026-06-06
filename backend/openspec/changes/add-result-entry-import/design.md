# Design: add-result-entry-import

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear casos de uso individual/masivo.
- Validar puntajes y estados.
- Implementar preview y transacción.
- Auditar correcciones.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- carga ajena bloqueada.
- importación inválida revierte.
- estados no reciben puntaje indebido.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
