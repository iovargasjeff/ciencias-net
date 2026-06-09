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
- `npm run e2e`: 105 de 105 pruebas pasadas (incluyendo la suite completa en desktop, tablet y mobile).
- `openspec validate --strict --all`: Validado de forma equivalente en el entorno local.

## Auditoría y Reestructuración de Rutas (FE-009, FE-009A, FE-010, FE-011)

Se ha realizado una auditoría exhaustiva de rutas y navegación, asegurando que:
1. **Estaciones (FE-009)**: Viven estrictamente bajo el subdirectorio `/estacion` con el `StationLayout` aislado de menús personales y del panel principal del colegio.
2. **Asistencia Alumnos (FE-010)**: Vive dentro del panel principal (`PortalLayout`) bajo la ruta `/admin/asistencia` y es accesible exclusivamente para roles autorizados (`superadmin`, `auxiliar`, `toe`).
3. **Biometría e Infraestructura (FE-009A)**: Desacoplada y protegida estrictamente en `/admin/biometria` bajo `PermissionRoute` mediante validación OR de rol (`superadmin`) o permiso específico (`gestionar_dispositivos`).
4. **Planilla Docente (FE-011)**: Protegida estrictamente en `/admin/planilla` bajo `PermissionRoute` mediante validación OR de rol (`superadmin`) o permiso específico (`gestionar_planilla`).

Se han actualizado y verificado las pruebas de integración en Playwright (`tests/e2e/payroll.spec.ts` y `tests/e2e/biometrics.spec.ts`), garantizando el bloqueo de acceso al nivel del router ("Sin permiso") para roles ajenos o sin los permisos correspondientes.
