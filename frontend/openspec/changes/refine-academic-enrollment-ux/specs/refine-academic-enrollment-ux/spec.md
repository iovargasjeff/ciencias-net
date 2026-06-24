# refine-academic-enrollment-ux Specification

## Purpose

Mejorar la experiencia academica para que coordinacion, matricula, evaluaciones y horarios usen filtros dependientes, datos reales y acciones segun permisos.

## ADDED Requirements

### Requirement: Identidad visual y sesion

La aplicacion SHALL mostrar `Ciencias Net` como title web y SHALL mostrar nombre/correo de la cuenta actual en el shell autenticado.

#### Scenario: title correcto

- GIVEN la aplicacion carga
- WHEN el navegador muestra el titulo
- THEN el titulo es `Ciencias Net`

### Requirement: Datos academicos por pestanias

La administracion academica SHALL organizar periodos, grados, secciones, cursos, matriculas y carga docente en pestanias o vistas claramente separadas.

#### Scenario: usuario navega tabs academicos

- GIVEN coordinacion abre estructura academica
- WHEN cambia a la pestania Cursos
- THEN ve cursos en formato de filas/columnas con acciones disponibles

### Requirement: Filtros dependientes academicos

La UI SHALL solicitar grado antes de listar secciones, cursos o alumnos dependientes.

#### Scenario: secciones segun grado

- GIVEN quinto tiene secciones A y B
- WHEN el usuario selecciona quinto
- THEN el selector de seccion muestra solo A y B

### Requirement: Matricula separada y buscable

La matricula SHALL estar en una vista separada y SHALL permitir buscar estudiantes por nombre o DNI antes de matricular.

#### Scenario: busqueda por DNI

- GIVEN existe un estudiante con DNI registrado
- WHEN el usuario busca ese DNI en matricula
- THEN la UI muestra el estudiante con nombre y datos utiles para seleccionarlo

### Requirement: Edicion por permisos

La UI SHALL habilitar acciones de edicion solo cuando el usuario tenga permiso y SHALL mostrar estado sin permiso cuando no lo tenga.

#### Scenario: boton editar habilitado

- GIVEN coordinacion tiene permiso de edicion
- WHEN abre un registro academico editable
- THEN el boton Editar esta habilitado

### Requirement: Evaluaciones por grado seccion curso

La UI SHALL permitir crear examenes solo despues de seleccionar grado, seccion y curso, y SHALL mostrar solo alumnos matriculados para registrar notas.

#### Scenario: notas solo con filtros completos

- GIVEN el usuario no ha seleccionado curso
- WHEN intenta crear examen
- THEN la UI mantiene la accion deshabilitada o muestra validacion de filtros requeridos

### Requirement: Horarios reales para alumno y docente

Las pantallas de horarios y calendario SHALL mostrar los horarios creados para el alumno o docente autenticado.

#### Scenario: docente ve horario creado

- GIVEN el backend devuelve horario del docente
- WHEN el docente abre calendario
- THEN la UI muestra los bloques recibidos y no un mock
