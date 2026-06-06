# Design: add-result-publication-ranking-reports

## Sources and Invariants

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Implementar publicación transaccional.
- Calcular ranking excluyendo estados.
- Crear consultas y reportes PDF.
- Notificar panel/correo y auditar corrección.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- privacidad alumno/padre pasa.
- empates y exclusiones pasan.
- corrección recalcula y notifica.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
