# Tasks: add-production-deployment-backups

## Source of Truth Check

- Product docs reviewed: `docs/README.md`
- Architecture docs reviewed: `docs/architecture/deployment.md`, `docs/architecture/deployment-runbook.md`
- API contracts reviewed: `backend/openspec/API_CONTRACTS.md` declares no new HTTP contract
- Domain docs reviewed: `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/overview.md`, `docs/security/audit-and-operations.md`, `docs/security/data-and-files.md`
- Conflicts found: no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
No backend domain code is required for this operations-only change.
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


## Implementation
- [x] 1.1 Crear configuración producción y secretos externos. Owner: André
- [x] 1.2 Automatizar backup integral y checksums. Owner: André
- [x] 1.3 Documentar restore y rollback. Owner: André
- [x] 1.4 Probar restauración aislada. Owner: André

## Verification
- [x] 2.1 Verificar que puertos privados verificados. Owner: André
- [x] 2.2 Verificar que restore trimestral documentado. Owner: André
- [x] 2.3 Verificar que alerta ante backup fallido. Owner: André

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
