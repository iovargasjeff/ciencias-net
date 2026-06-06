# Design: verify-backend-release

## Sources and Invariants

- `../../../../docs/README.md`
- `../../../../docs/product/approved-requirements.md`
- `../../../../docs/security/overview.md`

## Technical Design

- Ejecutar suite completa.
- Revisar matriz de permisos y rendimiento.
- Validar OpenAPI, migraciones y backups.
- Registrar pendientes explícitos.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- suite y contrato pasan.
- sin vulnerabilidades críticas.
- restore y smoke producción pasan.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
