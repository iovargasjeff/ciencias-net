# Backend Agents

## Equipo

| Persona | Responsabilidad principal | Puede apoyar |
|---|---|---|
| Jefferson | Arquitectura backend, APIs, auth, casos de uso y revisiones | Base de datos, seguridad, integración |
| Fátima | PostgreSQL, migraciones, constraints, índices y módulos backend | APIs, pruebas, optimización |
| André | Docker, despliegue, configuración, integración facial e infraestructura | Backend, observabilidad, seguridad |

Los owners del plan son responsables iniciales, no límites rígidos. Jefferson, Fátima y André pueden implementar
changes backend; cada change debe conservar owner y reviewer diferentes.

## Lectura obligatoria

1. `Rules.md`
2. `AGENTS.md`
3. `openspec/EXECUTION_PLAN.md`
4. `../docs/` relacionados
5. `openspec/specs/` relacionados
6. Changes activos y código relacionado

## Coordinación

- No implementar un change bloqueado por una spec pendiente.
- Publicar OpenAPI/spec aceptada antes de desbloquear frontend.
- André revisa impacto Docker/deploy de nuevas dependencias o servicios.
- Fátima revisa migraciones, constraints e índices.
- Jefferson revisa contratos API, límites de módulos y autorización.

