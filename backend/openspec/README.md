# OpenSpec Backend

Organiza migraciones, casos de uso, APIs, seguridad, integraciones e infraestructura backend.

## Flujo

1. Elegir el siguiente change desbloqueado de `EXECUTION_PLAN.md`.
2. Completar su propuesta, diseño, delta spec, tareas y criterios de verificación.
3. Implementar únicamente el alcance aprobado.
4. Verificar pruebas, migraciones, seguridad y contrato OpenAPI.
5. Archivar la capacidad aceptada en `specs/` y cerrar el plan.

La Fase 0 debe terminar antes de repartir features funcionales. Los owners son líderes iniciales; Jefferson, Fátima y
André pueden apoyar cualquier change backend manteniendo un reviewer diferente.

## Fuentes

- `../../docs/`: reglas compartidas y decisiones autoritativas.
- `COVERAGE.md`: trazabilidad entre dominios documentados y changes backend.
- `specs/`: capacidades backend aceptadas y estables.
- `changes/`: trabajo propuesto o activo; no es contrato estable para frontend.
- OpenAPI/Scribe: contrato HTTP publicado para integración.

Cada change activo contiene `proposal.md`, `design.md`, `tasks.md`, `verification.md` y al menos una delta spec en
`specs/<capability>/spec.md`.
