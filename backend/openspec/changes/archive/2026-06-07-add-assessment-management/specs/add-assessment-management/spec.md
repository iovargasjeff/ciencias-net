# add-assessment-management Specification

## Purpose

Configurar evaluaciones físicas y su ciclo de revisión.

## ADDED Requirements

### Requirement: 1

Coordinación SHALL crear evaluaciones válidas

#### Scenario: queda en borrador

- GIVEN define examen para carga activa
- WHEN guarda
- THEN queda en borrador

### Requirement: 2

Una evaluación cerrada SHALL cambiar solo tras reapertura auditada

#### Scenario: se rechaza

- GIVEN docente intenta editar cerrada
- WHEN envía cambio
- THEN se rechaza

