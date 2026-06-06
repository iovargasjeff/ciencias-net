# add-communications-notifications Specification

## Purpose

Publicar comunicaciones segmentadas y registrar su entrega/lectura.

## ADDED Requirements

### Requirement 1

Un usuario SHALL recibir solo comunicados de su segmento

#### Scenario: no aparece

- GIVEN se publica para otro grado
- WHEN usuario consulta
- THEN no aparece

### Requirement 2

Marcar leído SHALL ser idempotente

#### Scenario: queda una lectura

- GIVEN usuario abre comunicado varias veces
- WHEN marca lectura
- THEN queda una lectura

