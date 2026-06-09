# Tasks: add-private-psychology-workflow

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
- [x] 1.1 Modelar `atenciones_psicologia`. Owner: Jefferson
- [x] 1.2 Implementar `CreatePsychologyCare` validando confidencialidad. Owner: Jefferson
- [x] 1.3 `PsychologyCarePolicy`. Owner: Jefferson

## Verification
- [x] 2.1 Verificar que `auxiliar` o `toe` reciba 403. Owner: Jefferson
- [x] 2.2 Verificar logs no expongan notas. Owner: Jefferson

## Review and Archive
- [x] 3.1 Revisar que la confidencialidad se mantenga. Reviewer: André
- [x] 3.2 Revisar y archivar la spec. Reviewer: André
