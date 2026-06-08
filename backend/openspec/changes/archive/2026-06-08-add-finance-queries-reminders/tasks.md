# Tasks: add-finance-queries-reminders

## Source of Truth Check

- Product docs reviewed: yes
- Architecture docs reviewed: yes
- API contracts reviewed: yes
- Domain docs reviewed: yes
- Security docs reviewed: yes
- Conflicts found: no

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
- [x] 1.1 Crear queries optimizadas y resources. Owner: André
- [x] 1.2 Aplicar Policies familiares. Owner: André
- [x] 1.3 Crear jobs de recordatorio. Owner: André
- [x] 1.4 Exponer reportes paginados. Owner: André

## Verification
- [x] 2.1 Verificar que alcance familiar probado. Owner: André
- [x] 2.2 Verificar que valores cambian al vencer fecha. Owner: André
- [x] 2.3 Verificar que EXPLAIN de morosos/caja revisado. Owner: André

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
