# Tasks: add-incidents-workflow

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
- [x] 1.1 Crear casos de uso e historial. Owner: Jefferson
- [x] 1.2 Aplicar Policies por rol/vínculo. Owner: Jefferson
- [x] 1.3 Integrar notificaciones y generar reporte paginado. Owner: Jefferson

## Verification
- [x] 2.1 Verificar flujo Auxiliar-TOE pasa. Owner: Jefferson
- [x] 2.2 Verificar padre ajeno no recibe acceso. Owner: Jefferson
- [x] 2.3 Verificar acciones quedan auditadas. Owner: Jefferson

## Review and Archive
- [x] 3.1 Revisar mapeos API-DB y roles autorizados. Reviewer: André
- [x] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
