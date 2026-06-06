# add-obligation-generation-adjustments Specification

## Purpose

Generar y ajustar deudas pendientes conservando el historial.

## ADDED Requirements

### Requirement 1

Generar obligación SHALL congelar beneficio y montos aplicables

#### Scenario: cada deuda conserva snapshot

- GIVEN existe configuración vigente
- WHEN se generan deudas
- THEN cada deuda conserva snapshot

### Requirement 2

Una deuda pagada SHALL ser inmutable

#### Scenario: se rechaza

- GIVEN Yanina intenta ajustarla
- WHEN envía cambio
- THEN se rechaza

