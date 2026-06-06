# add-communications-notifications Specification

## Purpose

Publicar, leer y archivar comunicaciones segmentadas.

## ADDED Requirements

### Requirement 1

Publicador SHALL previsualizar destinatarios

#### Scenario: ve alcance esperado

- GIVEN configura segmento
- WHEN revisa antes de publicar
- THEN ve alcance esperado

### Requirement 2

Usuario SHALL marcar lectura al abrir

#### Scenario: estado cambia a leído

- GIVEN abre comunicado
- WHEN contenido carga
- THEN estado cambia a leído

