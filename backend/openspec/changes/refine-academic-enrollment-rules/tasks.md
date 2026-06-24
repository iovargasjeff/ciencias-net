# Tasks: refine-academic-enrollment-rules

## Source of Truth Check

- Product docs reviewed: `docs/product/approved-requirements.md`; `CAMBIOS CIENCIASNET.docx` no está disponible localmente, por lo que OpenSpec se usa como fuente suficiente para este change.
- Architecture docs reviewed: `docs/architecture/database-schema.md`, `docs/architecture/backend.md`
- API contracts reviewed: `docs/api/openapi.yaml`, paquetes `API-ACADEMIC`, `API-ASSESSMENTS`, `API-SCHEDULES`
- Domain docs reviewed: `docs/domain/academic.md`, `docs/domain/use-case-catalog.md`
- Security docs reviewed: `docs/security/README.md`
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
- [x] 1.1 Actualizar contratos OpenAPI de estructura academica, evaluaciones y horarios con filtros y validaciones. Owner: Jefferson
- [x] 1.2 Implementar periodos por anio con bimestres fechados y validaciones de solapamiento. Owner: Jefferson
- [x] 1.3 Reemplazar creacion libre de grados por catalogo/listado predefinido y bloquear duplicados. Owner: Jefferson
- [x] 1.4 Validar secciones por grado, capacidad maxima y cupos de matricula. Owner: Jefferson
- [x] 1.5 Requerir grado/seccion en matricula y permitir busqueda por nombre o DNI. Owner: Jefferson
- [x] 1.6 Requerir grado en cursos y bloquear cursos duplicados por grado/periodo/nombre. Owner: Jefferson
- [x] 1.7 Implementar carga docente por grado, seccion y curso con filtros dependientes. Owner: Jefferson
- [x] 1.8 Generar horarios de curso, docente y alumno desde la carga academica. Owner: Jefferson
- [x] 1.9 Ajustar evaluaciones para crear examenes y notas desde grado, seccion y curso. Owner: Jefferson

## Verification
- [x] 2.1 Verificar que no se puedan crear grados/cursos duplicados. Owner: Jefferson
- [x] 2.2 Verificar que una matricula no exceda el limite de estudiantes de la seccion. Owner: Jefferson
- [x] 2.3 Verificar que al seleccionar grado solo se devuelvan sus secciones/cursos activos. Owner: Jefferson
- [x] 2.4 Verificar que busqueda por DNI y nombre funcione para alumno y docente. Owner: Jefferson
- [x] 2.5 Verificar que docente/alumno ven horarios reales generados. Owner: Jefferson
- [x] 2.6 Verificar que evaluaciones solo incluyan alumnos matriculados en curso/seccion. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentacion afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Andre
