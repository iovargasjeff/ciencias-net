# Tasks: refine-identity-family-role-rules

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`, `CAMBIOS CIENCIASNET.docx`
- Architecture docs reviewed: `docs/architecture/database-schema.md`, `docs/architecture/backend.md`
- API contracts reviewed: `docs/api/openapi.yaml`, paquetes IAM, FAMILY, COMMUNICATIONS, FINANCE-QUERIES, INCIDENTS, PSYCHOLOGY
- Domain docs reviewed: `docs/domain/identity-access.md`, `docs/domain/finance.md`, `docs/domain/incidents-communications.md`
- Security docs reviewed: `docs/security/README.md`, `docs/security/data-and-files.md`
- Conflicts found: no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.

## Implementation
- [ ] 1.1 Actualizar OpenAPI para altas/ediciones por rol y perfil de sesion. Owner: Fatima
- [ ] 1.2 Implementar validaciones diferenciadas para staff, docente, padre y alumno. Owner: Fatima
- [ ] 1.3 Bloquear creacion/asignacion ordinaria de superadmin. Owner: Fatima
- [ ] 1.4 Exponer nombre, correo, roles y permisos efectivos de la cuenta actual. Owner: Fatima
- [ ] 1.5 Mejorar vinculos familiares con filtros por grado/seccion y busqueda por DNI/apellido/nombre. Owner: Fatima
- [ ] 1.6 Corregir comunicados para persistir destinatarios y entregar al publico seleccionado. Owner: Fatima
- [ ] 1.7 Corregir estado de cuenta para padre/alumno desde hijos vinculados y obligaciones reales. Owner: Fatima
- [ ] 1.8 Revisar permisos de administrativo, TOE, auxiliar, incidencias, rostros y psicologia. Owner: Fatima

## Verification
- [ ] 2.1 Verificar campos requeridos por rol y DNI/email unicos. Owner: Fatima
- [ ] 2.2 Verificar que superadmin no pueda crearse ni asignarse por API ordinaria. Owner: Fatima
- [ ] 2.3 Verificar que familia filtre por grado y no vincule entidades invalidas. Owner: Fatima
- [ ] 2.4 Verificar que comunicados lleguen solo a destinatarios seleccionados. Owner: Fatima
- [ ] 2.5 Verificar que padres no vean hijos ajenos ni estado de cuenta mock. Owner: Fatima
- [ ] 2.6 Verificar permisos permitidos y denegados por rol. Owner: Fatima

## Review and Archive
- [ ] 3.1 Publicar contratos/documentacion afectados. Owner: Fatima
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
