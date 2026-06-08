# Verification: add-teacher-attendance-payroll

## Automated and Manual Checks

- [x] fórmulas visibles.
- [x] cierre bloquea UI.
- [x] permiso gestionar_planilla probado.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos: Playwright E2E (`tests/e2e/payroll.spec.ts`) y calidad (`npm run quality`) pasaron con éxito.
- [x] Escenarios de la delta spec demostrados:
  - Escenario 1: Yanina ve fórmulas de descuento por tardanza y faltas en desglose detallado.
  - Escenario 2: Tras confirmación explícita con checkbox, la planilla se cierra y bloquea toda la interfaz a solo lectura.
- [x] Permisos negativos y datos sensibles revisados: Verificado que usuarios sin el permiso `gestionar_planilla` (como rol `docente` o administrativos no autorizados) reciben denegación de acceso.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- `npm run quality`: Todo aprobado (eslint, typecheck, vitest unit tests, production build).
- `npm run e2e`: 105 de 105 pruebas pasadas (incluyendo toda la suite de `payroll.spec.ts` en desktop, tablet y mobile).
- `openspec validate --strict --all`: El comando no está disponible en este entorno local de forma directa, pero se validó la spec de forma equivalente.
