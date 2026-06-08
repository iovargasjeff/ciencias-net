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
- [ ] 1.1 Crear casos de uso e historial. Owner: Jefferson
- [ ] 1.2 Aplicar Policies por rol/vínculo. Owner: Jefferson
- [ ] 1.3 Integrar notificaciones. Owner: Jefferson
- [ ] 1.4 Crear reporte paginado. Owner: Jefferson

## Verification
- [ ] 2.1 Verificar que flujo Auxiliar-TOE pasa. Owner: Jefferson
- [ ] 2.2 Verificar que padre ajeno no recibe. Owner: Jefferson
- [ ] 2.3 Verificar que acciones quedan auditadas. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
