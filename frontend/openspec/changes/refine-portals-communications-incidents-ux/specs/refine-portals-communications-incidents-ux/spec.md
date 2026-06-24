# refine-portals-communications-incidents-ux Specification

## Purpose

Corregir experiencia de usuarios, familia, comunicaciones, materiales, incidencias, psicologia, asistencia y portales por rol para que usen datos reales, permisos correctos y componentes consistentes.

## ADDED Requirements

### Requirement: Formulario dinamico de usuarios

El gestor de usuarios SHALL mostrar campos segun rol: datos base para todos, campos de docente, padre o alumno cuando correspondan, y staff simplificado sin campos no requeridos.

#### Scenario: rol docente muestra campos obligatorios

- GIVEN el gestor selecciona rol docente
- WHEN se renderiza el formulario
- THEN aparecen apellidos, DNI y telefono como obligatorios

### Requirement: Superadmin no asignable

La UI SHALL impedir crear nuevas cuentas superadmin o asignar ese rol desde administracion ordinaria.

#### Scenario: superadmin oculto

- GIVEN un administrador abre el selector de roles
- WHEN crea o edita una cuenta ordinaria
- THEN superadmin no aparece como opcion asignable

### Requirement: Vinculos familiares usables

La UI SHALL filtrar alumnos por grado/seccion, buscar padres/alumnos por DNI o apellido y confirmar antes de eliminar vinculos.

#### Scenario: eliminar vinculo requiere confirmacion

- GIVEN existe un vinculo familiar
- WHEN el usuario presiona eliminar
- THEN aparece un dialogo de confirmacion antes de enviar la peticion

### Requirement: Comunicados con destinatarios reales

La UI SHALL mostrar destinatarios seleccionados al crear comunicados y SHALL listar historial con entregas reales.

#### Scenario: comunicado dirigido

- GIVEN el usuario selecciona quinto A como destinatario
- WHEN envia el comunicado
- THEN el historial muestra quinto A y los usuarios de otros grados no lo reciben

### Requirement: Materiales y audio validados

La UI SHALL manejar materiales de estudio incluyendo audio/archivo/enlace con validaciones, progreso, error y descarga autorizada.

#### Scenario: audio invalido visible

- GIVEN un docente intenta subir un audio invalido
- WHEN el backend rechaza el archivo
- THEN la UI muestra el error sin perder el formulario

### Requirement: Incidencias TOE y psicologia con design system

Las pantallas de TOE, incidencias y psicologia SHALL usar componentes del sistema de diseno, estados completos y permisos correctos.

#### Scenario: psicologia carga registros

- GIVEN psicologia tiene permiso
- WHEN abre registros de atencion
- THEN la pantalla muestra datos guardados con layout consistente

### Requirement: Asistencia auxiliar legible

La asistencia manual SHALL permitir buscar alumnos por nombre/DNI, mostrar DNI nombre grado y seccion, y recargar resultados correctamente.

#### Scenario: registro manual no muestra UUID

- GIVEN auxiliar busca un alumno
- WHEN aparece el resultado
- THEN la UI muestra nombre y DNI como dato principal

### Requirement: Portales por rol sin mocks

Los portales de docente, alumno, padre y administrativo SHALL consultar datos reales y respetar permisos; estado de cuenta no SHALL mostrar hijos u obligaciones mock.

#### Scenario: padre ve estado real

- GIVEN el padre tiene un hijo vinculado
- WHEN abre estado de cuenta
- THEN ve obligaciones del hijo vinculado y no datos mock

### Requirement: Alumno solo lectura en notas e incidencias

El alumno SHALL poder ver notas e incidencias tipo agenda si tiene permiso, pero no crear ni modificar esos registros.

#### Scenario: alumno no edita notas

- GIVEN un alumno abre sus notas
- WHEN la pantalla carga
- THEN no aparecen acciones para agregar o modificar notas
