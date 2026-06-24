# Proposal: refine-portals-communications-incidents-ux

**ID:** FE-025  
**Fase:** Fase 7: Ajustes post revision  
**Owner:** Vincenzo  
**Reviewer:** Kiara  
**Dependencias:** FE-002, FE-003, FE-006, FE-008, FE-014, FE-017, FE-019, FE-020, FE-021, Backend BE-030

## Why

La revision detecto formularios de usuarios incompletos por rol, vinculos familiares dificiles de usar, comunicados que no llegan al destinatario seleccionado, vistas de TOE/psicologia/incidencias con HTML sin diseño, errores 403, datos mock en estado de cuenta, materiales/audio por revisar y portales con acciones incorrectas para alumnos, padres, docentes y superadmin.

## In Scope

- formulario dinamico de usuarios por rol
- bloqueo visual para crear/asignar superadmin
- vinculos familiares filtrados por grado y combobox por DNI/apellido
- estados loading/vacio/error/exito y confirmaciones criticas
- comunicados con historial y destinatarios seleccionados
- materiales de estudio con revision de audio/descarga/alcance
- TOE, psicologia e incidencias con componentes del design system
- correccion de 403 mostrando permisos reales
- asistencia auxiliar con busqueda por nombre/DNI y datos legibles
- estado de cuenta de alumno/padre con datos reales
- portales de docente, alumno, padre, administrativo y superadmin con permisos coherentes

## Out of Scope

- Cambios de backend fuera de BE-030.
- Reemplazar el design system existente.

## Impact

- Proyecto: `frontend`.
- Capacidades: usuarios, familia, comunicaciones, materiales, incidencias, psicologia, asistencia, finanzas, portales por rol.

## API Contract

- Consume contratos actualizados de `API-IAM`, `API-FAMILY`, `API-COMMUNICATIONS`, `API-MATERIALS`, `API-FILES`, `API-INCIDENTS`, `API-PSYCHOLOGY`, `API-FINANCE-QUERIES` y `API-STUDENT-ATTENDANCE`.

## Source Documents

- `../../../../docs/architecture/frontend.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/finance.md`
- `../../../../docs/api/openapi.yaml`
- `C:/Users/andre/Downloads/CAMBIOS CIENCIASNET.docx`
