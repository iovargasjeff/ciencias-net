# add-production-deployment-backups Specification

## Purpose

Desplegar y recuperar el sistema completo de forma documentada.

## ADDED Requirements

### Requirement 1

Producción SHALL exponer solo HTTPS necesario

#### Scenario: DB y Python siguen privados

- GIVEN stack está desplegado
- WHEN se inspeccionan puertos
- THEN DB y Python siguen privados

### Requirement 2

Backup SHALL restaurar datos y archivos

#### Scenario: el sistema recupera alcance definido

- GIVEN existe respaldo cifrado
- WHEN se prueba restauración
- THEN el sistema recupera alcance definido

