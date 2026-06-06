# Proposal: add-family-student-portal-shell

**ID:** FE-008  
**Fase:** Fase 1: Identidad y academia  
**Owner:** Vincenzo  
**Reviewer:** Kiara  
**Dependencias:** FE-003, Backend BE-005/BE-006/BE-007

## Why

Dar a padres y alumnos un portal de consulta con contexto seguro.

## In Scope

- selector de hijo/contexto
- inicio con accesos permitidos
- historial y navegación solo lectura
- estado de consentimiento/perfil biométrico sin exponer datos

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `frontend`.
- Capacidades: selector de hijo/contexto, inicio con accesos permitidos, historial y navegación solo lectura, estado de consentimiento/perfil biométrico sin exponer datos.

## Source Documents

- `../../../../docs/product/roles-and-permissions.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/security/data-and-files.md`
