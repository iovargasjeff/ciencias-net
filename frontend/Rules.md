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
- CSS para transiciones comunes; GSAP solo para secuencias complejas justificadas.
- Respetar `prefers-reduced-motion`.
- Cada pantalla cubre loading, vacío, error, éxito y sin permiso.
- Diseño responsive y accesibilidad WCAG AA.

## Calidad

- Contratos/tipos alineados con OpenAPI backend aceptado.
- Vitest y Testing Library para lógica/componentes; Playwright para flujos críticos.
- No cerrar changes con errores de consola, rutas rotas o estados sin manejar.
