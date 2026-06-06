# initialize-docker-development Specification

## Purpose

Permitir levantar todo el entorno de forma reproducible.

## ADDED Requirements

### Requirement 1

El entorno SHALL iniciar con un comando documentado

#### Scenario: los servicios saludables quedan disponibles

- GIVEN un clon limpio tiene Docker
- WHEN se ejecuta Compose
- THEN los servicios saludables quedan disponibles

### Requirement 2

PostgreSQL y Python SHALL permanecer en red privada

#### Scenario: no están expuestos a Internet

- GIVEN los servicios están activos
- WHEN se inspeccionan puertos
- THEN no están expuestos a Internet

