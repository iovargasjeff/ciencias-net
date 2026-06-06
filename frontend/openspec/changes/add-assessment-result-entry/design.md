# Design: add-assessment-result-entry

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`

## Technical Design

- Crear páginas de evaluaciones.
- Crear tabla editable de notas.
- Crear importador con preview.
- Mostrar estados y permisos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- carga ajena ausente.
- preview errores probado.
- teclado y tabla responsive.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
