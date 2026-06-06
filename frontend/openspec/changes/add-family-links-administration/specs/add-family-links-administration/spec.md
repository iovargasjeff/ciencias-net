# add-family-links-administration Specification

## Purpose

Gestionar perfiles y vínculos familiares sin autorregistro.

## ADDED Requirements

### Requirement 1

Gestor SHALL vincular varios padres e hijos

#### Scenario: los vínculos quedan visibles

- GIVEN abre perfil alumno
- WHEN selecciona padres
- THEN los vínculos quedan visibles

### Requirement 2

UI SHALL confirmar desvinculación

#### Scenario: lista se actualiza

- GIVEN gestor retira vínculo
- WHEN confirma acción
- THEN lista se actualiza

