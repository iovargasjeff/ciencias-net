# Proposal: refine-academic-enrollment-ux

**ID:** FE-024  
**Fase:** Fase 7: Ajustes post revision  
**Owner:** Kiara  
**Reviewer:** Vincenzo  
**Dependencias:** FE-002, FE-003, FE-005, FE-007, FE-015, FE-018, Backend BE-029

## Why

La revision detecto problemas de navegacion y captura en coordinacion academica: title incorrecto, cuadros que deben ser pestanias, datos academicos mezclados, matricula no separada, boton editar desactivado, filtros dependientes incompletos y horarios/calendario mostrando informacion que no corresponde.

## In Scope

- title de la web como `Ciencias Net`
- vista de cuenta actual con nombre y correo
- organizacion por pestanias de periodos, grados, secciones, cursos, matriculas y carga docente
- matricula como seccion/pantalla separada
- filtros dependientes grado -> seccion -> curso
- busqueda por nombre y DNI
- edicion habilitada donde el usuario tenga permiso
- evaluaciones y notas por grado, seccion y curso
- horarios/calendario mostrando horarios creados para alumno y docente
- correccion de sombreado de inicio

## Out of Scope

- Cambios backend no contemplados en BE-029.
- Redisenio global del design system.

## Impact

- Proyecto: `frontend`.
- Capacidades: academico, matricula, evaluaciones, horarios, navegacion base.

## API Contract

- Consume contratos actualizados de `API-ACADEMIC`, `API-ASSESSMENTS`, `API-SCHEDULES` y `API-IAM`.

## Source Documents

- `../../../../docs/architecture/frontend.md`
- `../../../../docs/domain/academic.md`
- `../../../../docs/api/openapi.yaml`
- `C:/Users/andre/Downloads/CAMBIOS CIENCIASNET.docx`
