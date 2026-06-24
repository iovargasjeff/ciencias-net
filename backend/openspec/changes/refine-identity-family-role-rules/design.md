# Design: refine-identity-family-role-rules

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`, `CAMBIOS CIENCIASNET.docx`
- Architecture docs reviewed: `docs/architecture/database-schema.md`, `docs/architecture/backend.md`
- API contracts reviewed: `docs/api/openapi.yaml`, paquetes IAM, FAMILY, COMMUNICATIONS, FINANCE-QUERIES, INCIDENTS, PSYCHOLOGY
- Domain docs reviewed: `docs/domain/identity-access.md`, `docs/domain/finance.md`, `docs/domain/incidents-communications.md`
- Security docs reviewed: `docs/security/README.md`, `docs/security/data-and-files.md`
- Conflicts found: no

If any conflict exists during implementation, do not implement until docs are corrected or the task is rewritten.

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

## Sources and Invariants

- Las reglas por rol del documento adjunto deben traducirse a validaciones server-side.
- Staff administrativo usa datos base y cargo derivado del rol; docente, padre y alumno exigen campos propios.
- Superadmin no se crea ni se asigna desde flujos ordinarios.
- Familia, comunicaciones, finanzas, incidencias y psicologia deben consultar datos reales y autorizados.

## Technical Design

- Crear/ajustar comandos de alta de cuenta por rol con DTOs/requests especificos.
- Validar `email` unico para `users`; validar DNI unico donde aplique.
- Derivar `cargo` administrativo desde rol seleccionado.
- Exponer endpoint de perfil de sesion con usuario, email, nombre, roles y permisos efectivos.
- Restringir creacion/asignacion de `superadmin` a seed/proceso controlado, no a endpoints de administracion general.
- Agregar filtros de familia por grado/seccion y busqueda por DNI/apellido/nombre.
- Validar payload `parent_account_id`, `student_id`, `relationship`, `es_contacto_principal`, `recibe_notificaciones`.
- Enviar/listar comunicados por destinatario seleccionado, con historial real y filtros de alcance.
- Corregir politicas 403 de incidencias, rostros, administrativo, TOE, auxiliar y psicologia segun matriz de permisos.
- Asegurar que estado de cuenta de padre/alumno use hijos vinculados y obligaciones reales, no datos mock.

## Security and Authorization

- Autorizacion obligatoria por policy/gate para cada accion.
- Las respuestas no exponen UUID internos cuando el usuario necesita DNI/nombre, salvo identificadores tecnicos necesarios.
- Padres solo ven hijos vinculados; alumnos solo ven sus datos; docentes solo ven asignaciones propias.
- Psicologia mantiene privacidad reforzada.

## Testing Strategy

- Pruebas de validacion por rol para staff, docente, padre y alumno.
- Pruebas de bloqueo de superadmin.
- Pruebas de perfil de sesion.
- Pruebas de familia por grado y busqueda por DNI/apellido.
- Pruebas de comunicados dirigidos.
- Pruebas de permisos que cubran 403 esperados y accesos permitidos.
- Pruebas de estado de cuenta sin mocks.

## Rejected Scope

- No relajar permisos para corregir 403 sin definir rol/capability.
- No duplicar cuentas si el usuario ya existe.
