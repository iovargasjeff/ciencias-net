# Design: refine-portals-communications-incidents-ux

## Sources and Invariants

- `CAMBIOS CIENCIASNET.docx`
- `../../../../docs/architecture/frontend.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/incidents-communications.md`
- `../../../../docs/domain/finance.md`
- `../../../../docs/api/openapi.yaml`

## Technical Design

- Crear formulario dinamico de usuarios: datos base siempre, campos extra por docente/padre/alumno y staff simplificado.
- Ocultar/bloquear alta o asignacion de superadmin en UI ordinaria; conservar edicion de perfil permitida.
- En vinculos familiares, usar combobox con busqueda por DNI/apellido/nombre y filtro previo por grado/seccion.
- Mostrar tabla de vinculos con alumno, padre/apoderado, parentesco, badges de configuracion y acciones.
- Usar confirmacion explicita antes de eliminar vinculo familiar.
- Corregir historial de comunicados y asegurar filtros/destinatarios visibles antes de enviar.
- En materiales, validar audio/archivos, estados de carga, error y alcance por matricula/carga docente.
- Rehacer TOE/cuaderno de incidencias/psicologia sobre componentes compartidos, no HTML crudo.
- Mostrar errores 403 como estado sin permiso con accion sugerida, y no como pantalla rota.
- En asistencia auxiliar, buscar alumnos por nombre/DNI y mostrar DNI/nombre/grado/seccion en vez de UUID o labels incorrectos.
- Reparar boton recargar con invalidacion/refetch real.
- En incidentes, corregir fecha, layout de registro y vista agenda solo lectura para alumnos/padres.
- En estado de cuenta, eliminar mocks y mostrar hijos/obligaciones devueltas por API.
- En docente, mostrar cursos asignados en evaluaciones.
- En alumno, ocultar o corregir estado de cuenta segun permiso; permitir ver incidencias tipo agenda sin modificar.
- En administrativo, mostrar modulos disponibles segun permisos reales.

## Security and Authorization

- UI basada en permisos efectivos; backend sigue siendo autoridad.
- No exponer datos sensibles de psicologia o incidencias a roles no autorizados.
- Confirmar acciones destructivas como eliminar vinculo familiar.

## Testing Strategy

- Tests de formulario dinamico por rol.
- Tests de familia con filtros, empty state y confirmacion.
- Tests de comunicados por destinatario e historial.
- Tests de materiales con audio/archivo invalido.
- Tests E2E de incidencias, psicologia y asistencia auxiliar.
- Tests de portales padre/alumno/docente/administrativo/superadmin con permisos y mocks apagados.

## Rejected Scope

- No dejar pantallas como HTML aislado fuera del shell.
- No presentar UUID como dato principal para seleccion humana.
