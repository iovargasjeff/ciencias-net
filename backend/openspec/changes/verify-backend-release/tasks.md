# Tasks: verify-backend-release

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`; `CAMBIOS CIENCIASNET.docx` no está disponible localmente, por lo que OpenSpec se usa como fuente suficiente para este change.
- Architecture docs reviewed: `docs/architecture/backend.md`, `docs/architecture/database-schema.md`, `docs/architecture/deployment-runbook.md`
- API contracts reviewed: `docs/api/openapi.yaml`, paquetes modificados en Fase 6 y Fase 7
- Domain docs reviewed: `docs/domain/academic.md`, `docs/domain/identity-access.md`, `docs/domain/finance.md`, `docs/domain/incidents-communications.md`
- Security docs reviewed: `docs/security/README.md`, `docs/security/data-and-files.md`
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
- [x] 1.1 Ejecutar suite completa. Owner: Jefferson
- [x] 1.2 Revisar matriz de permisos y rendimiento. Owner: Jefferson
- [x] 1.3 Validar OpenAPI, migraciones y backups. Owner: Jefferson
- [x] 1.4 Registrar pendientes explícitos. Owner: Jefferson

## Verification
- [x] 2.1 Verificar que suite y contrato pasan. Owner: Jefferson
- [x] 2.2 Verificar que sin vulnerabilidades críticas. Owner: Jefferson
- [x] 2.3 Verificar que restore y smoke producción pasan. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
