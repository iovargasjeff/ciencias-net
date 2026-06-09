# add-finance-queries-reminders Specification

## Purpose

Entregar estados de cuenta, morosidad, caja y recordatorios con alcance correcto.

## Requirements

### Requirement: 1

Un padre SHALL ver solo obligaciones de hijos vinculados

#### Scenario: se rechaza sin filtrar datos

- GIVEN consulta estado de hijo ajeno
- WHEN envía solicitud
- THEN se rechaza sin filtrar datos

### Requirement: 2

El estado SHALL mostrar monto pronto pago antes del límite

#### Scenario: muestra 450 hasta fecha y 480 después

- GIVEN la obligación es elegible
- WHEN se consulta
- THEN muestra 450 hasta fecha y 480 después
