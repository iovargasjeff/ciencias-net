# Nuevo Change Frontend

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

## Diseño mínimo

- Rutas y permisos.
- Pantallas, componentes y estados.
- Contratos API aceptados utilizados.
- Responsive, accesibilidad y animación.
- Caché e invalidaciones.
- Pruebas unitarias, componentes y E2E.

No se acepta `specs/README.md` como sustituto de una delta spec. Las specs frontend describen rutas, estados y
comportamiento observable; no repiten detalles de implementación.
