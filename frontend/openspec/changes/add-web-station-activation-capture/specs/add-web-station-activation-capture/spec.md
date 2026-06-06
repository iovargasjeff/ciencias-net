# add-web-station-activation-capture Specification

## Purpose

Operar estaciones web limitadas en celular, tablet o PC multicámara.

## ADDED Requirements

### Requirement 1

Estación SHALL activarse sin sesión humana

#### Scenario: abre captura técnica

- GIVEN equipo recibe código válido
- WHEN lo ingresa
- THEN abre captura técnica

### Requirement 2

PC SHALL seleccionar varias cámaras

#### Scenario: cada una muestra modo y estado

- GIVEN navegador detecta cámaras
- WHEN responsable configura
- THEN cada una muestra modo y estado

