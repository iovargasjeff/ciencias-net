# add-private-psychology-portal Specification

## Purpose

Ofrecer un espacio explícitamente privado para Psicología y superadmin.

## ADDED Requirements

### Requirement 1

Portal privado SHALL ser visible solo a Psicología/superadmin

#### Scenario: muestra sin permiso sin datos

- GIVEN otro rol conoce ruta
- WHEN navega
- THEN muestra sin permiso sin datos

### Requirement 2

Notas SHALL evitar exposición incidental

#### Scenario: resumen no muestra contenido privado innecesario

- GIVEN Psicología abre listado
- WHEN se renderiza
- THEN resumen no muestra contenido privado innecesario

