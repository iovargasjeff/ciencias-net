# refine-identity-family-role-rules Specification

## Purpose

Corregir identidad, roles, familia, permisos y consultas asociadas para que cada rol cree y consulte solo datos requeridos, reales y autorizados.

## ADDED Requirements

### Requirement: Perfil de sesion visible

El backend SHALL exponer los datos de la cuenta autenticada incluyendo nombre, correo, roles y permisos efectivos.

#### Scenario: usuario consulta su sesion

- GIVEN un usuario autenticado
- WHEN solicita su perfil de sesion
- THEN recibe nombre, correo, roles y permisos efectivos

### Requirement: Alta de usuarios por rol

El backend SHALL validar campos requeridos segun rol: staff usa datos base, docente requiere apellidos DNI y telefono, padre requiere apellidos DNI celular y correo de notificaciones, alumno requiere apellidos y DNI.

#### Scenario: docente sin DNI rechazado

- GIVEN se crea una cuenta con rol docente
- WHEN falta DNI
- THEN el sistema rechaza la solicitud

#### Scenario: staff sin datos extra aceptado

- GIVEN se crea una cuenta staff administrativo con nombres email y password
- WHEN no se envia DNI ni telefono
- THEN el sistema crea la cuenta y deriva cargo desde el rol

### Requirement: Superadmin protegido

El backend SHALL impedir crear nuevas cuentas superadmin o asignar el rol superadmin desde endpoints ordinarios de administracion.

#### Scenario: asignacion superadmin rechazada

- GIVEN un administrador gestiona cuentas
- WHEN intenta asignar superadmin a otro usuario
- THEN el sistema rechaza la operacion

### Requirement: Vinculos familiares filtrables

El backend SHALL permitir filtrar estudiantes por grado/seccion y buscar padres o alumnos por DNI, apellido o nombre antes de crear vinculos familiares.

#### Scenario: filtro por grado antes de vincular

- GIVEN existen alumnos en cuarto y quinto
- WHEN se filtra por quinto
- THEN el sistema muestra solo alumnos de quinto para vincular

### Requirement: Payload familiar valido

La creacion de vinculo familiar SHALL requerir padre, alumno y parentesco valido, y SHALL soportar flags de contacto principal y notificaciones.

#### Scenario: parentesco invalido rechazado

- GIVEN se intenta crear un vinculo familiar
- WHEN `relationship` no es padre, madre o apoderado
- THEN el sistema rechaza el payload

### Requirement: Comunicados dirigidos

Los comunicados SHALL persistir destinatarios y SHALL entregarse solo a los roles, grados, secciones o personas seleccionadas.

#### Scenario: comunicado a una seccion

- GIVEN se publica un comunicado para quinto A
- WHEN un padre de quinto B consulta comunicados
- THEN ese comunicado no aparece

### Requirement: Estado de cuenta real por vinculo familiar

El estado de cuenta SHALL consultar obligaciones reales de los estudiantes vinculados al padre autenticado.

#### Scenario: padre ve solo hijos vinculados

- GIVEN un padre esta vinculado a un estudiante
- WHEN consulta estado de cuenta
- THEN recibe obligaciones de ese estudiante y no datos mock ni hijos ajenos

### Requirement: Permisos por rol corregidos

El backend SHALL autorizar funciones de administrativo, TOE, auxiliar, psicologia, incidencias y rostros segun permisos declarados y SHALL devolver 403 solo cuando corresponda.

#### Scenario: rol autorizado accede

- GIVEN un usuario TOE tiene permiso para incidencias
- WHEN consulta incidencias permitidas
- THEN el sistema responde correctamente y no devuelve 403
