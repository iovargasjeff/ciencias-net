# Tasks: verify-backend-release

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
- [ ] 1.1 Ejecutar suite completa. Owner: Jefferson
- [ ] 1.2 Revisar matriz de permisos y rendimiento. Owner: Jefferson
- [ ] 1.3 Validar OpenAPI, migraciones y backups. Owner: Jefferson
- [ ] 1.4 Registrar pendientes explícitos. Owner: Jefferson

## Verification
- [ ] 2.1 Verificar que suite y contrato pasan. Owner: Jefferson
- [ ] 2.2 Verificar que sin vulnerabilidades críticas. Owner: Jefferson
- [ ] 2.3 Verificar que restore y smoke producción pasan. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
