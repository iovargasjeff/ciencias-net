# Tasks: add-finance-schema

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
- [x] 1.1 Crear migraciones financieras. Owner: Fátima
- [x] 1.2 Agregar checks, FKs, índices y unicidad. Owner: Fátima
- [x] 1.3 Modelar estados e inmutabilidad. Owner: Fátima
- [x] 1.4 Probar consultas y rollback. Owner: Fátima

## Verification
- [x] 2.1 Verificar que constraints de montos pasan. Owner: Fátima
- [x] 2.2 Verificar que referencias duplicadas fallan. Owner: Fátima
- [x] 2.3 Verificar que rollback completo. Owner: Fátima

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: Fátima
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
