# Tasks: add-biometric-enrollment-consent

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
- [x] 1.1 Crear casos de uso de consentimiento/enrolamiento. Owner: André
- [x] 1.2 Integrar storage R2 privado. Owner: André
- [x] 1.3 Cifrar embeddings con clave separada. Owner: André
- [x] 1.4 Auditar y publicar endpoints. Owner: André

## Verification
- [x] 2.1 Verificar que enrolamiento sin consentimiento bloqueado. Owner: André
- [x] 2.2 Verificar que revocación desactiva. Owner: André
- [x] 2.3 Verificar que objetos privados no son públicos. Owner: André

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: André
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
