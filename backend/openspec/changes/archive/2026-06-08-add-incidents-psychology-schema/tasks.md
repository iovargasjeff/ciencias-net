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
- [x] 1.1 Crear migración para las tablas requeridas. Owner: Fátima
- [x] 1.2 Modelar `Incidencia`, `HistorialIncidencia` en el módulo respectivo. Owner: Fátima
- [x] 1.3 Modelar `AtencionPsicologica` en el módulo de Psicología. Owner: Fátima

## Verification
- [x] 2.1 Escribir test validando foráneas y restricciones Enum. Owner: Fátima
- [x] 2.2 Verificar rollback exitoso. Owner: Fátima

## Review and Archive
- [x] 3.1 Revisar privacidad y separación de módulos. Reviewer: André
- [x] 3.2 Revisar y archivar la spec aceptada. Reviewer: André
