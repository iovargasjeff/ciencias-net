# Proposal: refine-academic-enrollment-rules

**ID:** BE-029  
**Fase:** Fase 7: Ajustes post revision  
**Owner:** Jefferson  
**Reviewer:** Andre  
**Dependencias:** BE-006, BE-018, BE-019, BE-022

## Why

La revision del documento `CAMBIOS CIENCIASNET.docx` detecto inconsistencias en estructura academica, matricula, cursos, carga docente, horarios y evaluaciones. Estas reglas deben quedar en backend para evitar duplicados, listados incorrectos y flujos que permitan crear notas u horarios fuera del contexto academico real.

## In Scope

- periodos por anio academico con bimestres fechados
- grados predefinidos y sin creacion libre desde UI
- secciones limitadas por grado y capacidad
- cursos unicos asociados a grado
- matricula por grado y seccion existente
- busqueda de estudiantes y docentes por nombre o DNI
- carga docente por grado, curso y seccion
- horarios de curso, docente y alumno derivados de la carga academica
- evaluaciones creadas solo despues de filtrar grado, seccion y curso

## Out of Scope

- Redisenio visual del frontend.
- Nuevos roles institucionales no declarados en los documentos fuente.
- Cambios de base de datos sin revisar `docs/architecture/database-schema.md`.

## Impact

- Proyecto: `backend`.
- Capacidades: estructura academica, matricula, evaluaciones, horarios.

## API Contract

- Declaracion contractual: modifica `API-ACADEMIC`, `API-ASSESSMENTS` y `API-SCHEDULES`.
- Actualizar `../../../../docs/api/paths/academic.yaml`, `assessments.yaml`, `schedules.yaml` y sus schemas/request-bodies antes de implementar si el contrato actual no cubre los filtros o validaciones.

## Source Documents

- `../../../../docs/domain/academic.md`
- `../../../../docs/domain/use-case-catalog.md`
- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/api/openapi.yaml`
- `C:/Users/andre/Downloads/CAMBIOS CIENCIASNET.docx`
