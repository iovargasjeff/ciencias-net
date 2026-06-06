# add-account-role-administration Specification

## Purpose

Permitir administrar cuentas y roles respetando restricciones.

## ADDED Requirements

### Requirement 1

Gestor SHALL administrar roles operativos

#### Scenario: la UI confirma resultado

- GIVEN gestor abre una cuenta
- WHEN edita rol permitido
- THEN la UI confirma resultado

### Requirement 2

UI SHALL ocultar y bloquear privilegios no permitidos

#### Scenario: la opción no existe y backend se respeta

- GIVEN gestor intenta asignar superadmin
- WHEN usa formulario
- THEN la opción no existe y backend se respeta

