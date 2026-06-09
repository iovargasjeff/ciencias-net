# Tasks: add-private-files-service

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
- [ ] 1.1 Crear servicio de archivos privados. Owner: André
- [ ] 1.2 Validar MIME/tamaño/checksum. Owner: André
- [ ] 1.3 Implementar URLs firmadas y limpieza. Owner: André
- [ ] 1.4 Agregar Policies y auditoría. Owner: André

## Verification
- [ ] 2.1 Verificar que storage:link no requerido. Owner: André
- [ ] 2.2 Verificar que URL expira. Owner: André
- [ ] 2.3 Verificar que limpieza elimina evidencia. Owner: André

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
