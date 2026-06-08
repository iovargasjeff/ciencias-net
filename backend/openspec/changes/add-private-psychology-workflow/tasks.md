# Tasks: add-private-psychology-workflow

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
- [ ] 1.1 Crear casos de uso privados. Owner: Jefferson
- [ ] 1.2 Implementar Policies explícitas. Owner: Jefferson
- [ ] 1.3 Separar Resources públicos/privados. Owner: Jefferson
- [ ] 1.4 Auditar accesos sensibles. Owner: Jefferson

## Verification
- [ ] 2.1 Verificar que matriz negativa completa. Owner: Jefferson
- [ ] 2.2 Verificar que logs no contienen notas. Owner: Jefferson
- [ ] 2.3 Verificar que superadmin y Psicología autorizados. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
