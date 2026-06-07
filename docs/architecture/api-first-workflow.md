# Flujo de Trabajo: API-First Design

Para evitar bloqueos entre desarrolladores (y agentes de IA) y asegurar un paralelismo real, el proyecto adopta la metodología **API-First Design (Contract-Driven)**.

## 1. Diseño del Contrato (Design-First)
Antes de escribir lógica de base de datos o componentes visuales, el Arquitecto o el Owner de la feature debe definir el contrato HTTP de la API.
- Se utiliza el estándar **OpenAPI 3.x**.
- **Múltiples archivos:** Para mantener el proyecto escalable, el contrato no se escribe en un solo archivo gigante. Se divide en múltiples documentos referenciados (ej. `paths/asistencia.yaml`, `schemas/Alumno.yaml`) dentro del directorio de documentación (ej. `docs/api/`).
- Una vez que el archivo YAML está definido y aprobado, se considera un "Contrato Firmado".

## 2. Desarrollo en Paralelo (Vertical Slicing)
Con el contrato publicado, el equipo se divide y trabaja sin dependencias bloqueantes:

### Frontend (Mocking)
- El desarrollador Frontend lee el contrato OpenAPI y configura herramientas de simulación (como **MSW - Mock Service Worker**) para interceptar las llamadas de la aplicación.
- Construye todas las pantallas, formularios, validaciones y manejo de estado asumiendo que el backend funciona perfectamente y devuelve los datos simulados.
- **Regla:** El Frontend no debe depender del estado activo del desarrollo Backend. Su única dependencia es el contrato publicado.

### Backend (Implementación)
- El desarrollador Backend lee el mismo contrato OpenAPI y diseña las migraciones, modelos, controladores y reglas de negocio necesarias para cumplir estrictamente con lo prometido en el YAML.

## 3. Integración (The "Junte")
Cuando el Backend finaliza y verifica su desarrollo, el Frontend simplemente **apaga los Mocks** temporales.
Al haberse basado ambos en el mismo contrato estricto desde el día 1, la integración final encaja perfectamente sin errores de formato de datos.

---

> **Nota sobre Asignaciones:** Para potenciar este flujo, el `EXECUTION_PLAN` asigna todas las micro-tareas de una misma feature (desde la migración hasta el último controlador, o desde las rutas hasta las vistas) a una **única persona** (Owner). Esto previene colisiones y asegura que un desarrollador tenga control End-to-End de su funcionalidad asignada.
