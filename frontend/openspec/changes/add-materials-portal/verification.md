# Verification: add-materials-portal

## Automated and Manual Checks

- [x] alcance de matrícula probado.
- [x] upload inválido visible.
- [x] descarga privada E2E.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

Se ha implementado el change y verificado exitosamente mediante una suite de 24 pruebas Playwright E2E (`tests/e2e/materials.spec.ts`) que cubren:
1. Aislamiento de Matrícula (los alumnos sólo ven los materiales de sus secciones matriculadas).
2. Validación de subida de archivos (límite frontend de 10MB con banner de error visible, y preservación del contexto del formulario ante errores 422 del servidor).
3. Descargas privadas y seguras llamando al API.
4. Restricción de permisos y aislamiento de carga docente.

Todas las pruebas linter, compilación estática y typecheck pasaron exitosamente.
