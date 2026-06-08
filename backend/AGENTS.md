# Backend Agents

## Regla para Codex, Antigravity y otros agentes

- Antes de implementar, contrastar el change con `../docs/architecture/backend.md`,
  `../docs/product/roles-and-permissions.md`, `../docs/domain/`, `../docs/security/` y `../docs/api/`.
- Si OpenSpec contradice `../docs/`, no se implementa. El agente reporta la contradiccion y propone corregir el change
  o el contrato, dando prioridad a `../docs/`.
- No crear codigo de dominio en `app/Models`, `app/Http/Controllers`, `app/UseCases` ni `app/Policies`.
- Usar `app/Modules/<Modulo>/...` para todo nuevo feature backend. Las carpetas raiz son legado transitorio.

## Equipo

| Persona | Responsabilidad principal | Puede apoyar |
|---|---|---|
| Jefferson | Arquitectura backend, APIs, auth, casos de uso y revisiones | Base de datos, seguridad, integración |
| Fátima | PostgreSQL, migraciones, constraints, índices y módulos backend | APIs, pruebas, optimización |
| André | Docker, despliegue, configuración, integración facial e infraestructura | Backend, observabilidad, seguridad |

Los owners del plan son responsables iniciales, no límites rígidos. Jefferson, Fátima y André pueden implementar
changes backend. El reviewer válido para cada change futuro es el indicado en `openspec/EXECUTION_PLAN.md`.

## Lectura obligatoria

1. `Rules.md`
2. `AGENTS.md`
3. `openspec/EXECUTION_PLAN.md`
4. `../docs/` relacionados
5. `openspec/specs/` relacionados
6. Changes activos y código relacionado

## Coordinación

- No implementar un change bloqueado por una spec pendiente.
- Al recibir una tarea, confirmar el ID/change y seguir `openspec/TASK_REQUEST.md`.
- Mantener sincronizados `EXECUTION_PLAN.md`, `tasks.md` y `verification.md` mientras se trabaja.
- Aprobar y publicar OpenAPI en `../docs/api/` antes de desbloquear frontend.
- André revisa impacto Docker/deploy de nuevas dependencias o servicios.
- Fátima revisa migraciones, constraints e índices.
- Jefferson revisa contratos API, límites de módulos y autorización.
