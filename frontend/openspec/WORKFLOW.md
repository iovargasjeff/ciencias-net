# Frontend OpenSpec Workflow

## Flujo

1. Confirmar que las specs/contratos backend necesarios estén aceptados.
2. Leer dominio, arquitectura frontend y seguridad relacionada.
3. Confirmar requisitos SHALL y escenarios de `specs/<capability>/spec.md`.
4. Definir pantallas, rutas, permisos, estados y responsive en `design.md`.
5. Implementar tareas pequeñas.
6. Verificar componentes, accesibilidad, rutas y E2E.
7. Registrar resultados, archivar spec, actualizar plan y crear commit enfocado.

## Cierre obligatorio

No cerrar un change con estados loading/error/vacío/sin permiso faltantes, errores de consola, accesibilidad pendiente,
contratos simulados o pruebas E2E críticas sin ejecutar.
