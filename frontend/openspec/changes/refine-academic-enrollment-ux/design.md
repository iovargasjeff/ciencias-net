# Design: refine-academic-enrollment-ux

## Sources and Invariants

- `CAMBIOS CIENCIASNET.docx`
- `../../../../docs/architecture/frontend.md`
- `../../../../docs/domain/academic.md`
- `../../../../docs/api/openapi.yaml`

## Technical Design

- Cambiar `document.title` y metadata visible del shell a `Ciencias Net`.
- Mostrar cuenta actual en el shell: nombre, correo y rol principal desde endpoint de sesion.
- Reestructurar coordinacion academica con tabs: Periodos, Grados, Secciones, Cursos, Matriculas, Carga docente.
- Mantener Matricula como ruta o tab propio, no mezclada dentro de estructura general.
- Usar componentes de tabla/grid existentes para filas y columnas en lugar de tarjetas repetidas cuando el contenido es tabular.
- Usar combobox/busqueda para DNI y nombre de alumno/docente.
- Encadenar selects: grado habilita secciones; grado habilita cursos activos; grado+seccion habilita alumnos matriculados.
- Habilitar boton Editar si el usuario tiene permiso y mostrar estado sin permiso si no lo tiene.
- En evaluaciones, no permitir crear examen hasta seleccionar grado, seccion y curso; mostrar alumnos matriculados para carga de notas.
- Para roles de solo lectura, permitir ver notas sin crear ni modificar.
- En horarios/calendario, consumir horarios creados y mostrar estados loading/error/vacio.
- Corregir sombreado/estado visual de inicio sin introducir estilos inconsistentes.

## Security and Authorization

- La UI oculta o deshabilita acciones sin permiso, pero trata el 403 del backend como fuente final.
- No exponer UUID como dato principal cuando DNI/nombre son relevantes para el usuario.

## Testing Strategy

- Pruebas de componentes para tabs y filtros dependientes.
- Mocks de loading, error, vacio, sin permiso y exito.
- E2E de matricula por grado/seccion.
- E2E de evaluacion y carga/solo lectura de notas.
- Pruebas responsive de las tablas principales.

## Rejected Scope

- No usar selects largos sin busqueda para personas.
- No mostrar datos mock si el endpoint responde vacio.
