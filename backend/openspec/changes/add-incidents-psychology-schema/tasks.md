# Tasks: add-incidents-psychology-schema

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
- [ ] 1.1 Crear migraciones e índices. Owner: Fátima
- [ ] 1.2 Separar campos privados. Owner: Fátima
- [ ] 1.3 Agregar FKs y estados. Owner: Fátima
- [ ] 1.4 Probar rollback y privacidad. Owner: Fátima

## Verification
- [ ] 2.1 Verificar que historial no se elimina. Owner: Fátima
- [ ] 2.2 Verificar que queries generales excluyen privado. Owner: Fátima
- [ ] 2.3 Verificar que rollback completo. Owner: Fátima

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Fátima
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
