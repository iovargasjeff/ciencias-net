# Verification: add-schedules-calendar-portals

## Automated and Manual Checks

- [x] vistas por rol pasan.
- [x] conflicto visible.
- [x] móvil usable.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

Las pruebas E2E automatizadas se ejecutaron satisfactoriamente para todos los roles y escenarios del change `FE-018`:
```bash
npx playwright test tests/e2e/schedules.spec.ts
```
Resultados:
- **18 passed** en 13.3 segundos (probando en proyectos desktop, tablet y mobile).
- Se validaron correctamente los accesos restringidos (padre/alumno no acceden a `/admin/horarios`).
- Se verificó el aislamiento por carga docente (Juan solo ve su clase de matemática).
- Se verificó la gestión de solapamientos 409 y preservación del formulario.
- Se verificó la creación de clases y actividades del calendario.
- Se verificó la vista de alumno/padre en `/portal/horarios` con aislamiento de matrícula e interactividad del calendario mensual.
