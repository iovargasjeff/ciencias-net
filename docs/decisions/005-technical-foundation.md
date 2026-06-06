# ADR-005: Fundación Técnica Definitiva

**Estado:** Aceptado

- Laravel 13 aporta la base API: rutas, controladores, Form Requests, API Resources, paginación, Sanctum, middleware,
  Policies y manejo de excepciones.
- Scribe/OpenAPI publica el contrato HTTP verificado; OpenSpec organiza el trabajo y las capacidades.
- La arquitectura backend será modular pragmática: capas completas solo cuando protejan complejidad real.
- Docker Compose es el entorno principal de desarrollo, integración y despliegue inicial.
- React + TypeScript + Vite es la base frontend.
- shadcn/ui y Tailwind forman el sistema visual; Phosphor Icons para React es la única librería de iconos.
- CSS resuelve transiciones comunes y GSAP queda reservado para animaciones complejas justificadas.
- La entidad de deuda se llama `obligaciones_pago`; `movimientos_pago` registra pagos reales, anulaciones y devoluciones.
