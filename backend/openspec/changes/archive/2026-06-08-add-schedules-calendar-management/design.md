# Design: add-schedules-calendar-management

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

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Crear casos de uso de horarios/calendario.
- Validar solapamientos y alcance.
- Exponer vistas por actor.
- Integrar sesiones de clase.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- solapamiento inválido rechazado.
- alcance familiar probado.
- día no laboral afecta sesiones.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
