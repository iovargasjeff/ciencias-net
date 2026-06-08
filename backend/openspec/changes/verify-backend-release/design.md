# Design: verify-backend-release

## Source of Truth Check

- Product docs reviewed:
- Architecture docs reviewed:
- API contracts reviewed:
- Domain docs reviewed:
- Security docs reviewed:
- Conflicts found: yes/no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


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
