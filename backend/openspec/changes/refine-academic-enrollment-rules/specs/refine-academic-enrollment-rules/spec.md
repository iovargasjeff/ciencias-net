# refine-academic-enrollment-rules Specification

## Purpose

Corregir reglas academicas para que periodos, grados, secciones, cursos, matriculas, cargas docentes, horarios y evaluaciones operen con datos reales, sin duplicados y con filtros dependientes.

## ADDED Requirements

### Requirement: Periodos y bimestres por anio academico

El backend SHALL permitir crear anios academicos con bimestres fechados y SHALL rechazar rangos invalidos o solapados dentro del mismo anio.

#### Scenario: bimestres validos

- GIVEN un anio academico 2026 sin bimestres
- WHEN coordinacion registra cuatro bimestres con fechas consecutivas
- THEN el sistema guarda los bimestres asociados al anio academico

#### Scenario: bimestres solapados rechazados

- GIVEN existe un primer bimestre del 2026-03-01 al 2026-05-01
- WHEN se intenta crear otro bimestre que cruza ese rango
- THEN el sistema rechaza la operacion con error de validacion

### Requirement: Grados predefinidos y no duplicados

El backend SHALL exponer un listado de grados predefinidos y SHALL impedir crear grados duplicados o fuera del catalogo aprobado.

#### Scenario: grado duplicado rechazado

- GIVEN el catalogo contiene quinto de secundaria
- WHEN se intenta crear nuevamente quinto de secundaria para el mismo contexto
- THEN el sistema rechaza la duplicidad

### Requirement: Secciones limitadas por grado y capacidad

Las secciones SHALL pertenecer a un grado, declarar limite de estudiantes y controlar cupos durante la matricula.

#### Scenario: secciones filtradas por grado

- GIVEN quinto de secundaria tiene secciones A y B
- WHEN se consultan secciones para quinto de secundaria
- THEN el sistema retorna solo A y B

#### Scenario: capacidad excedida rechazada

- GIVEN la seccion A tiene limite de 30 estudiantes y ya tiene 30 matriculas activas
- WHEN se intenta matricular otro estudiante en esa seccion
- THEN el sistema rechaza la matricula por cupo agotado

### Requirement: Matricula por grado y seccion existente

La matricula SHALL requerir estudiante, grado y seccion existentes, y SHALL permitir buscar estudiantes por nombre o DNI.

#### Scenario: matricula con seccion de otro grado rechazada

- GIVEN la seccion A pertenece a cuarto de secundaria
- WHEN se intenta matricular un estudiante en quinto de secundaria usando esa seccion
- THEN el sistema rechaza la inconsistencia grado-seccion

### Requirement: Cursos asociados a grado

Todo curso SHALL estar asociado a un grado y SHALL ser unico por grado, periodo academico y nombre normalizado.

#### Scenario: curso repetido rechazado

- GIVEN existe Matematica para quinto de secundaria en 2026
- WHEN se crea otro curso Matematica para quinto de secundaria en 2026
- THEN el sistema rechaza el duplicado

### Requirement: Carga docente por grado, curso y seccion

La asignacion docente SHALL seleccionar primero grado, luego cursos activos de ese grado y seccion correspondiente.

#### Scenario: cursos activos filtrados

- GIVEN un docente sera asignado a quinto A
- WHEN se selecciona quinto de secundaria
- THEN el sistema lista solo cursos activos de quinto y secciones de quinto

### Requirement: Horarios derivados de cursos y cargas docentes

El backend SHALL generar o exponer horarios de curso, docente y alumno desde la configuracion academica real.

#### Scenario: horario docente derivado

- GIVEN un curso tiene horario lunes 08:00 y esta asignado a un docente
- WHEN el docente consulta su horario
- THEN el sistema muestra ese bloque horario

### Requirement: Evaluaciones filtradas por grado, seccion y curso

La creacion de examenes y carga de notas SHALL requerir grado, seccion y curso, y SHALL listar solo estudiantes matriculados en ese curso/seccion.

#### Scenario: lista de notas acotada a matriculados

- GIVEN quinto A tiene 25 estudiantes matriculados y quinto B tiene 20
- WHEN se crea una evaluacion para quinto A Matematica
- THEN la lista de notas contiene solo los 25 estudiantes de quinto A
