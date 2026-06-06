# harden-security-observability Specification

## Purpose

Aplicar controles transversales, auditoría y observabilidad antes de producción.

## ADDED Requirements

### Requirement 1

Logs SHALL excluir secretos, biometría y notas privadas

#### Scenario: solo contiene metadatos permitidos

- GIVEN ocurre operación sensible
- WHEN se registra log
- THEN solo contiene metadatos permitidos

### Requirement 2

Operaciones sensibles SHALL quedar auditadas

#### Scenario: existe evento trazable

- GIVEN se ajusta deuda o rol
- WHEN termina operación
- THEN existe evento trazable

