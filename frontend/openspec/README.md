# OpenSpec Frontend

Organiza pantallas, navegación, componentes, integración API, accesibilidad y pruebas frontend.

## Flujo

1. Elegir el siguiente change desbloqueado de `EXECUTION_PLAN.md`.
2. Completar propuesta, diseño, delta spec, tareas y verificación.
3. Confirmar que los contratos backend requeridos estén aceptados y publicados.
4. Implementar estados completos: carga, vacío, error, éxito y sin permiso.
5. Verificar responsive, accesibilidad, pruebas y E2E antes de archivar.

La Fase 0 entrega una base clonable y consistente. Kiara y Vincenzo pueden apoyar cualquier feature manteniendo owner y
reviewer diferentes.

## Fuentes

- `../../docs/`: reglas compartidas y decisiones autoritativas.
- `COVERAGE.md`: trazabilidad entre experiencias documentadas y changes frontend.
- `../../backend/openspec/specs/` y OpenAPI publicado: contratos estables.
- `specs/`: capacidades frontend aceptadas.
- `changes/`: trabajo propuesto o activo.

Cada change activo contiene `proposal.md`, `design.md`, `tasks.md`, `verification.md` y al menos una delta spec en
`specs/<capability>/spec.md`.
