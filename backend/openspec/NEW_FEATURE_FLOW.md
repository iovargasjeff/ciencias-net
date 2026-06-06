# Nuevo Change Backend

Cada change debe contener:

```text
changes/<change-name>/
├── proposal.md
├── design.md
├── tasks.md
├── verification.md
└── specs/
    └── <capability>/
        └── spec.md       # Requisitos SHALL y escenarios GIVEN/WHEN/THEN
```

## Alcance recomendado

Un change debe entregar una capacidad backend verificable y pequeña: esquema, API, integración o endurecimiento. Si
incluye más de un módulo grande o no puede cerrarse en pocos días, dividirlo.

## Diseño mínimo

- Reglas e invariantes.
- Tablas/constraints/índices y rollback.
- Casos de uso y endpoints.
- Auth, permisos y auditoría.
- Integraciones y secretos.
- Pruebas y rendimiento.
- Dependencias frontend/backend/infra.

No se acepta `specs/README.md` como sustituto de una delta spec. Cada capability debe declarar comportamiento
observable y verificable.
