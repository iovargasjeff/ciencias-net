# Design: add-facial-service-integration

## Sources and Invariants

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/detailed-system-design.md`
- `../../../../docs/security/authentication-authorization.md`

## Technical Design

- Implementar cliente Laravel y API FastAPI.
- Reemplazar el motor placeholder de hashes por `face_recognition` sin cambiar el contrato HTTP interno.
- Autenticar red privada y sincronización.
- Aplicar timeout y circuit handling.
- Agregar pruebas de contrato.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- contrato Laravel/Python pasa.
- servicio no accede PostgreSQL.
- timeout no crea asistencia.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.


## Source of Truth Check

- Product docs reviewed: `../../../../docs/product/roles-and-permissions.md` not directly changed by this implementation.
- Architecture docs reviewed: `../../../../docs/architecture/facial-integration.md`, `../../../../docs/architecture/detailed-system-design.md`.
- API contracts reviewed: `../../../../docs/api/internal/facial-openapi.yaml`; no wire contract changes required.
- Domain docs reviewed: attendance/facial behavior reviewed through existing Fase 2 OpenSpec artifacts.
- Security docs reviewed: `../../../../docs/security/authentication-authorization.md`.
- Conflicts found: no.

## Backend Placement

- Laravel integration remains under `backend/app/Modules/Usuarios/Infrastructure/Facial/` and `backend/app/Modules/Asistencia/Presentation/Controllers/`.
- Facial recognition engine remains in the private `facial-service/` FastAPI service.
- No domain code is introduced under root `app/`.
