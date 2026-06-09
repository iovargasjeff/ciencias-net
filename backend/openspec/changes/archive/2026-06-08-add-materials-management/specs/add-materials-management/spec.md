# add-materials-management Specification

## Purpose

Publicar recursos académicos privados para matrículas autorizadas.

## ADDED Requirements

### Requirement: 1

Solo matrícula activa SHALL descargar material

#### Scenario: se rechaza

- GIVEN alumno ajeno solicita archivo
- WHEN consulta endpoint
- THEN se rechaza

### Requirement: 2

Archivos SHALL servirse mediante endpoint autorizado

#### Scenario: recibe stream o URL corta

- GIVEN existe material privado
- WHEN usuario autorizado descarga
- THEN recibe stream o URL corta

