# add-identity-academic-schema Specification

## Purpose

Crear la base relacional de personas, familias, auditoría y estructura académica.

## ADDED Requirements

### Requirement 1

Cada correo humano SHALL ser único

#### Scenario: la base rechaza duplicado

- GIVEN ya existe una cuenta con correo
- WHEN se intenta crear otra
- THEN la base rechaza duplicado

### Requirement 2

Una matrícula SHALL vincular alumno, sección y periodo

#### Scenario: queda una pertenencia académica trazable

- GIVEN existen entidades válidas
- WHEN se crea matrícula
- THEN queda una pertenencia académica trazable

