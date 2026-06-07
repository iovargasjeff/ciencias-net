# add-evaluation-content-schema Specification

## Purpose

Persistir evaluaciones, resultados, reportes, materiales, horarios y comunicación académica.
Este change crea exclusivamente el esquema de base de datos (Sin contrato HTTP).
Las operaciones HTTP son responsabilidad de BE-018 al BE-023.

## Requirements

### Requirement 1

Una nota SHALL pertenecer a una matrícula y examen compatibles (únicos).

#### Scenario: constraint rechaza nota duplicada

- GIVEN ya existe una nota para el mismo examen y matrícula
- WHEN se intenta insertar otra nota para ese par
- THEN la base rechaza el duplicado por UNIQUE (examen_id, matricula_id)

### Requirement 2

Una lectura de comunicado SHALL ser única por comunicado y usuario.

#### Scenario: PK compuesta rechaza lectura duplicada

- GIVEN un usuario ya marcó un comunicado como leído
- WHEN se intenta marcar como leído nuevamente
- THEN la base rechaza el duplicado por PK compuesta (comunicado_id, user_id)

### Requirement 3

Un puntaje de nota SHALL ser mayor o igual a cero cuando no sea nulo.

#### Scenario: CHECK rechaza puntaje negativo en PostgreSQL

- GIVEN se intenta guardar una nota con puntaje negativo
- WHEN se ejecuta el INSERT en PostgreSQL
- THEN el CHECK constraint notas_puntaje_no_negativo rechaza la operación

### Requirement 4

Un horario SHALL tener hora_fin mayor a hora_inicio.

#### Scenario: CHECK rechaza horario inválido

- GIVEN se intenta crear un horario con hora_fin <= hora_inicio
- WHEN se ejecuta el INSERT en PostgreSQL
- THEN el CHECK constraint horarios_horas_validas rechaza la operación
