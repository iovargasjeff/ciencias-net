# Frontend Rules

## Arquitectura

- Leer primero `../docs/README.md`, dominio relacionado y specs backend aceptadas.
- No depender de changes backend activos como contrato estable.
- React + TypeScript + Vite, organizado por features.
- Estado local primero; Zustand solo para estado transversal necesario.
- Datos remotos con TanStack Query y Axios `withCredentials`.
- Formularios con React Hook Form y Zod.

## Navegación y seguridad

- React Router con layouts, rutas protegidas y verificación de permisos para UX.
- El backend sigue siendo autoridad; manejar correctamente `401`, `403`, `404` y errores de validación.
- Nunca almacenar tokens, secretos o datos sensibles en `localStorage`.
- Estaciones web usan sesión técnica y layout separados del portal humano.

## Sistema visual

- Tailwind CSS y shadcn/ui como base.
- Phosphor Icons para React es la única librería de iconos.
- **Regla Estricta de UI:** Queda prohibido instalar nuevas dependencias en el `package.json` relacionadas a UI, componentes extra, o importar tipografías ajenas. Todo debe reutilizar los tokens de Tailwind y shadcn/ui. Cualquier excepción o necesidad de una nueva librería debe ser aprobada explícitamente por el arquitecto (Jefferson) para evitar un exceso de dependencias.
- CSS para transiciones comunes; GSAP solo para secuencias complejas justificadas.
- Respetar `prefers-reduced-motion`.
- Cada pantalla cubre loading, vacío, error, éxito y sin permiso.
- Diseño responsive y accesibilidad WCAG AA.

## Calidad

- Contratos/tipos alineados con OpenAPI backend aceptado.
- Vitest y Testing Library para lógica/componentes; Playwright para flujos críticos.
- No cerrar changes con errores de consola, rutas rotas o estados sin manejar.

## Ejecución obligatoria de OpenSpec

- Toda tarea frontend debe identificar un único change de `openspec/EXECUTION_PLAN.md`.
- Antes de editar código, leer `Rules.md`, `AGENTS.md`, `openspec/WORKFLOW.md`, todos los artefactos del change, sus
  documentos fuente y contratos backend aceptados relacionados.
- Al iniciar trabajo real, cambiar el estado del change de `[ ]` a `[~]` en `openspec/EXECUTION_PLAN.md`.
- Marcar una casilla de `tasks.md` únicamente después de implementar y comprobar esa tarea.
- Registrar comandos, resultados E2E, accesibilidad, responsive, limitaciones y evidencias en `verification.md`.
- Si aparece un bloqueo real, marcar `[-]` y documentarlo sin cerrar el change.
- El owner implementa y verifica; el reviewer aprueba el cierre y archivado.
- Solo después de cumplir todas las tareas, escenarios, verificaciones y revisión:
  1. Fusionar cada delta spec en `openspec/specs/<capability>/spec.md`.
  2. Mover el change completo a `openspec/changes/archive/YYYY-MM-DD-<change-name>/`.
  3. Cambiar su estado a `[x]` en `openspec/EXECUTION_PLAN.md`.
- No marcar `[x]` con casillas pendientes, mocks temporales o contratos simulados.
