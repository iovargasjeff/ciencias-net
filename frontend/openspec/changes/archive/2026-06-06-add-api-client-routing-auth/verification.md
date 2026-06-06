# Verification: add-api-client-routing-auth

## Automated and Manual Checks

- [x] login/logout/recuperación/419 E2E.
- [x] localStorage sin tokens.
- [x] estación bloqueada del portal.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Contrato y documentos sincronizados.

## Results

- `npm run quality` pasa lint, typecheck, 2 pruebas unitarias y build.
- El contenedor oficial Playwright pasa 18 escenarios en móvil, tablet y escritorio.
- E2E cubre login, logout, recuperación genérica, error 419 y rutas protegidas.
- E2E demuestra que no aparecen claves de token/auth en `localStorage`.
- Un contexto de estación que intenta abrir `/portal` es devuelto a `/estacion/captura`.
- Axe no detecta violaciones serias o críticas y no aparecen errores de consola en la superficie pública.
- La aplicación usa el contrato OpenAPI aceptado de BE-003; los dobles de red existen únicamente dentro de pruebas E2E.
