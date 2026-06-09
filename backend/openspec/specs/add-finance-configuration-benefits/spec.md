# add-finance-configuration-benefits Specification

## Purpose

Permitir que la cuenta financiera configure montos y beneficios futuros.

## ADDED Requirements

### Requirement: 1

Solo gestionar_finanzas SHALL modificar configuración

#### Scenario: se rechaza

- GIVEN administrativo sin permiso intenta cambiar monto
- WHEN envía solicitud
- THEN se rechaza

### Requirement: 2

Un beneficio SHALL definir modalidad, valor, alcance y vigencia

#### Scenario: queda disponible para obligaciones futuras

- GIVEN Yanina crea beneficio válido
- WHEN confirma
- THEN queda disponible para obligaciones futuras

