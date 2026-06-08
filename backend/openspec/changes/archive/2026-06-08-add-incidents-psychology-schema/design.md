# Design: add-incidents-psychology-schema

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
