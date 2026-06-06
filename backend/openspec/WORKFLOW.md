# Backend OpenSpec Workflow

## Estados

- `[ ]` pendiente
- `[~]` en progreso
- `[-]` bloqueado
- `[x]` terminado, verificado, archivado y committeado

## Flujo

1. Confirmar dependencias aceptadas.
2. Leer reglas de dominio, arquitectura, seguridad y esquema relacionados.
3. Confirmar que `specs/<capability>/spec.md` contiene requisitos SHALL y escenarios GIVEN/WHEN/THEN.
4. Actualizar `proposal.md` y `design.md` si cambia el alcance.
5. Ejecutar tareas pequeñas en orden.
6. Verificar migraciones, pruebas, autorización, rendimiento y OpenAPI aplicables.
7. Registrar resultados en `verification.md`.
8. Validar y archivar el change.
9. Confirmar spec aceptada en `openspec/specs/`.
10. Actualizar `EXECUTION_PLAN.md` y crear commit enfocado.

## Cierre obligatorio

Un change backend no se marca `[x]` si falta contrato API, pruebas negativas de permisos, rollback de migración,
verificación de consultas o documentación requerida.
