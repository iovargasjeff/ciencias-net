# Backend Rules

Estas reglas son obligatorias para cualquier cambio backend.

## Arquitectura

- Leer primero `../docs/README.md`, documentos de dominio relacionados y specs aceptadas.
- Usar capacidades nativas de Laravel antes de crear abstracciones propias.
- Mantener arquitectura modular pragmática; no crear capas vacías para CRUD simples.
- Controladores validan/autorizan y llaman casos de uso; no contienen reglas ni consultas complejas.
- Reglas críticas y transacciones pertenecen al backend.
- El servicio facial nunca accede directamente a PostgreSQL ni decide asistencia.

## API

- El contrato OpenAPI aprobado en `../docs/api/` es la fuente autoritativa.
- API versionada bajo `/api/v1`.
- Form Requests para validar entrada y API Resources para salida.
- Policies para acceso al recurso; middleware para permisos generales.
- Listados paginados, filtros explícitos y formato de error estable.
- Scribe documenta la implementación y debe revisarse contra OpenAPI; no sobrescribe el contrato aprobado.
- Operaciones sensibles o reintentables usan idempotencia.

## Datos

- Migraciones solo en `backend/database/migrations/`.
- La entidad de deuda es `obligaciones_pago`; los pagos reales viven en `movimientos_pago`.
- No modificar históricos pagados/publicados/cerrados; usar movimientos o ajustes auditados.
- Foreign keys, constraints e índices deben acompañar cada esquema.
- Toda operación masiva o financiera usa transacción.
- Verificar consultas importantes con datos representativos y `EXPLAIN ANALYZE`.

## Seguridad y calidad

- Sanctum SPA para humanos; sesiones/credenciales técnicas separadas para estaciones e integraciones.
- Nunca registrar secretos, biometría ni notas psicológicas privadas en logs.
- Agregar pruebas unitarias, integración, feature y autorización según riesgo.
- Un change no termina sin verificación, contrato actualizado y documentación aplicable.

## Ejecución obligatoria de OpenSpec

- Toda tarea backend debe identificar un único change de `openspec/EXECUTION_PLAN.md`.
- Antes de editar código, leer `Rules.md`, `AGENTS.md`, `openspec/WORKFLOW.md`, todos los artefactos del change, sus
  documentos fuente, contratos OpenAPI aprobados y specs aceptadas relacionadas.
- Al iniciar trabajo real, cambiar el estado del change de `[ ]` a `[~]` en `openspec/EXECUTION_PLAN.md`.
- Marcar una casilla de `tasks.md` únicamente después de implementar y comprobar esa tarea. No marcar tareas por
  anticipado ni todas juntas al final.
- Registrar comandos, resultados, limitaciones y evidencias en `verification.md` durante el trabajo.
- Si aparece un bloqueo real, marcar `[-]`, explicar el bloqueo en `verification.md` y no fingir que el change terminó.
- El owner puede completar implementación y verificación, pero el reviewer debe aprobar el cierre y archivado.
- Solo después de cumplir todas las tareas, escenarios, verificaciones, contratos y revisión:
  1. Fusionar cada delta spec en `openspec/specs/<capability>/spec.md`.
  2. Mover el change completo a `openspec/changes/archive/YYYY-MM-DD-<change-name>/`.
  3. Cambiar su estado a `[x]` en `openspec/EXECUTION_PLAN.md`.
- No eliminar historial del change ni marcar `[x]` dejando casillas pendientes.

## Fuente de Verdad y Ubicacion Modular

- Leer primero `../docs/architecture/backend.md`, `../docs/product/roles-and-permissions.md`, los documentos de dominio
  y seguridad relacionados, y los contratos relevantes en `../docs/api/`.
- `../docs/` manda sobre cualquier `openspec/changes/*/tasks.md`. Si hay contradiccion, detenerse y reportarla antes
  de implementar.
- No crear modelos, controllers, requests, resources, use cases ni policies de dominio en la raiz de `app/`.
- Todo nuevo codigo de dominio debe ubicarse en `app/Modules/<Modulo>/`:
  `Domain`, `Application`, `Infrastructure` y `Presentation` segun corresponda.
- Al crear o modificar migraciones de un comportamiento visible, revisar primero `../docs/api/schemas/` y mantener
  nombres de columnas/enumeradores lo mas cerca posible del contrato HTTP.
- Todo `design.md` y `tasks.md` activo debe incluir `Source of Truth Check` y `Backend Placement`. Si falta, agregarlo
  antes de implementar.
