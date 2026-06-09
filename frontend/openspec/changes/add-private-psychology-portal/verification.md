# Verification: add-private-psychology-portal

## Automated and Manual Checks

- [x] matriz negativa E2E.
- [x] sin notas en consola/URL.
- [x] superadmin/Psicología pasan.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

Las pruebas automatizadas `npm run e2e tests/e2e/psychology.spec.ts` simulan la sesión del psicólogo (con acceso completo) y de otros roles (denegado). Además, no se incluye información sensible en URL ni se expone el ID de los estudiantes de forma pública.
