# Proposal: refine-identity-family-role-rules

**ID:** BE-030  
**Fase:** Fase 7: Ajustes post revision  
**Owner:** Fatima  
**Reviewer:** Jefferson  
**Dependencias:** BE-004, BE-005, BE-017, BE-023, BE-024, BE-025

## Why

La revision detecto que el gestor de usuarios no diferencia correctamente los datos requeridos por rol, hay dudas de permisos para administrativo/TOE/incidencias/rostros, los vinculos familiares requieren filtros por grado y los comunicados/estado de cuenta deben consultar datos reales segun destinatario.

## In Scope

- validaciones de alta/edicion de usuarios por rol
- bloqueo para crear o asignar superadmin desde UI/API ordinaria
- exposicion de identidad de sesion con nombre y correo
- busqueda y vinculacion familiar por grado, DNI, apellido y alumno
- comunicaciones dirigidas al publico seleccionado
- permisos de administrativo, TOE, auxiliar, psicologia, docente, alumno y padre
- estado de cuenta y portal familiar consistente con hijos vinculados reales

## Out of Scope

- Redisenio visual de pantallas.
- Nuevos canales de notificacion externos.
- Cambios de roles institucionales sin actualizar `docs/domain/identity-access.md`.

## Impact

- Proyecto: `backend`.
- Capacidades: identidad, roles, familia, comunicaciones, finanzas de consulta, incidencias y psicologia.

## API Contract

- Declaracion contractual: modifica `API-IAM`, `API-FAMILY`, `API-COMMUNICATIONS`, `API-FINANCE-QUERIES`, `API-INCIDENTS` y `API-PSYCHOLOGY`.

## Source Documents

- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/finance.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/api/openapi.yaml`
- `C:/Users/andre/Downloads/CAMBIOS CIENCIASNET.docx`
