# Tasks: add-materials-management

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
- [ ] 1.1 Crear casos de uso y Policies. Owner: André
- [ ] 1.2 Validar MIME/tamaño/integridad. Owner: André
- [ ] 1.3 Guardar en storage privado. Owner: André
- [ ] 1.4 Exponer endpoints y auditoría. Owner: André

## Verification
- [ ] 2.1 Verificar que archivo público directo imposible. Owner: André
- [ ] 2.2 Verificar que matrícula inactiva bloqueada. Owner: André
- [ ] 2.3 Verificar que validación de upload pasa. Owner: André

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
