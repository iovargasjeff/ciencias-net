# Design: refine-academic-enrollment-rules

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`; `CAMBIOS CIENCIASNET.docx` no está disponible localmente, por lo que OpenSpec se usa como fuente suficiente para este change.
- Architecture docs reviewed: `docs/architecture/database-schema.md`, `docs/architecture/backend.md`
- API contracts reviewed: `docs/api/openapi.yaml`, `docs/api/paths/academic.yaml`, `docs/api/paths/assessments.yaml`, `docs/api/paths/schedules.yaml`
- Domain docs reviewed: `docs/domain/academic.md`, `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/README.md`
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

- OpenSpec requiere evitar cursos/grados duplicados, usar grados predefinidos, vincular matricula a grado/seccion y crear evaluaciones desde filtros academicos.
- Las reglas criticas de duplicidad, capacidad, permisos y filtros deben residir en backend.
- El frontend puede ocultar opciones, pero el backend SHALL rechazar payloads incoherentes.

## Technical Design

- Agregar catalogo/listado oficial de grados predefinidos y bloquear creacion libre de grado si el modelo actual lo permite.
- Validar unicidad de periodo academico por anio, bimestres con fechas validas y secciones unicas por grado.
- Validar capacidad maxima de seccion y exponer cupos disponibles para matricula.
- Requerir `grade_id` al crear cursos; rechazar cursos duplicados por grado, periodo y nombre normalizado.
- Requerir grado antes de listar secciones, cursos activos y alumnos matriculados.
- Permitir busquedas normalizadas por nombre y DNI para estudiantes y docentes.
- Modelar carga docente por docente, grado, seccion y curso; validar que el curso pertenece al grado seleccionado.
- Exponer horario de alumno y docente desde horarios de cursos/carga docente, sin depender de mocks.
- Crear evaluaciones solo para curso activo filtrado por grado/seccion; la lista de notas SHALL incluir solo alumnos matriculados en esa seccion y curso.

## Security and Authorization

- Solo coordinador academico o roles autorizados pueden crear/editar periodos, secciones, cursos, cargas, horarios y evaluaciones.
- Docente puede consultar sus cursos y registrar notas solo si esta asignado.
- Alumno/padre solo consulta horarios, cursos y notas publicadas, sin modificar.
- Todas las validaciones de alcance se aplican por backend aunque el frontend filtre.

## Testing Strategy

- Pruebas de validacion para duplicados de grado/curso/seccion.
- Pruebas de capacidad de seccion y matricula fuera de grado.
- Pruebas de filtros dependientes grado -> seccion -> curso.
- Pruebas de permisos por coordinador, docente, alumno y padre.
- Pruebas de horario derivado para alumno/docente.
- Pruebas de evaluacion que lista solo alumnos matriculados.

## Rejected Scope

- No reescribir modulos ya archivados si basta con extender casos de uso existentes.
- No crear endpoints paralelos para el mismo recurso sin actualizar OpenAPI.
