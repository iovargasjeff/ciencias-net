# add-private-files-service Specification

## Purpose

Centralizar archivos privados y evitar storage público accidental.

## ADDED Requirements

### Requirement 1

Un archivo privado SHALL requerir autorización

#### Scenario: se rechaza

- GIVEN usuario no autorizado conoce identificador
- WHEN intenta descargar
- THEN se rechaza

### Requirement 2

Evidencia biométrica SHALL expirar

#### Scenario: se elimina y audita

- GIVEN objeto alcanzó expira_en
- WHEN corre limpieza
- THEN se elimina y audita

