# Tasks: add-schedules-calendar-management

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


## Implementation
- [x] 1.1 Crear casos de uso de horarios/calendario. Owner: André
- [x] 1.2 Validar solapamientos y alcance. Owner: André
- [x] 1.3 Exponer vistas por actor. Owner: André
- [x] 1.4 Integrar sesiones de clase. Owner: André

## Verification
- [x] 2.1 Verificar que solapamiento inválido rechazado. Owner: André
- [x] 2.2 Verificar que alcance familiar probado. Owner: André
- [x] 2.3 Verificar que día no laboral afecta sesiones. Owner: André

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: André
- [x] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
