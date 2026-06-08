# Tasks: add-result-publication-ranking-reports

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
- [x] 1.1 Implementar publicación transaccional. Owner: Jefferson
- [x] 1.2 Calcular ranking excluyendo estados. Owner: Jefferson
- [x] 1.3 Crear consultas y reportes PDF. Owner: Jefferson
- [x] 1.4 Notificar panel/correo y auditar corrección. Owner: Jefferson

## Verification
- [x] 2.1 Verificar que privacidad alumno/padre pasa. Owner: Jefferson
- [x] 2.2 Verificar que empates y exclusiones pasan. Owner: Jefferson
- [x] 2.3 Verificar que corrección recalcula y notifica. Owner: Jefferson

## Review and Archive
- [x] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [x] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
