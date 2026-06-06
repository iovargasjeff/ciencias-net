# add-roles-permissions-account-management Specification

## Purpose

Delegar cuentas y roles sin entregar privilegios sensibles por accidente.

## ADDED Requirements

### Requirement 1

Solo superadmin SHALL asignar superadmin o gestor_usuarios

#### Scenario: la operación se rechaza y audita

- GIVEN un gestor intenta asignarlos
- WHEN envía el cambio
- THEN la operación se rechaza y audita

### Requirement 2

Gestor de usuarios SHALL administrar roles operativos sin modificar los propios

#### Scenario: la operación se rechaza

- GIVEN el gestor edita su cuenta
- WHEN intenta cambiar roles
- THEN la operación se rechaza

