# add-biometric-attendance-schema Specification

## Purpose

Persistir consentimiento, estaciones, reconocimientos y asistencia con trazabilidad.

## ADDED Requirements

### Requirement 1

Solo SHALL existir un perfil facial activo por persona

#### Scenario: la base rechaza conflicto

- GIVEN una persona ya tiene perfil activo
- WHEN se activa otro
- THEN la base rechaza conflicto

### Requirement 2

Un movimiento SHALL pertenecer a alumno o docente, nunca ambos

#### Scenario: constraint rechaza

- GIVEN se intenta guardar relación inválida
- WHEN la transacción ejecuta
- THEN constraint rechaza

