# CienciasNET Frontend

SPA React, TypeScript y Vite organizada por features. El entorno oficial se levanta desde la raíz con Docker Compose.

```bash
docker compose up -d --build
docker compose exec frontend npm run lint
docker compose exec frontend npm run test
docker compose exec frontend npm run build
```

## Sistema visual

- Tailwind CSS y componentes accesibles adaptados al producto.
- Phosphor Icons es la única librería de iconos.
- CSS resuelve transiciones comunes.
- GSAP se reserva para secuencias complejas que aporten comprensión, siempre respetando `prefers-reduced-motion`.
- Toda pantalla debe cubrir loading, vacío, error, éxito y sin permiso.

## Sesión y rutas

- Axios usa `withCredentials` y obtiene CSRF desde Sanctum antes del login.
- La sesión humana vive en cookie `httpOnly`; no se guardan tokens en `localStorage`.
- `/portal` y `/admin` pasan por guards de sesión/permisos.
- `/estacion/*` conserva un contexto técnico separado y no puede navegar al portal humano.

```bash
docker compose exec frontend npm run quality
docker compose --profile test run --rm frontend-e2e
```
