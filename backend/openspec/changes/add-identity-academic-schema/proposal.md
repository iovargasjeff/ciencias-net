# Proposal: add-identity-academic-schema

**ID:** DB-001  
**Fase:** Fase 1: Identidad y estructura académica  
**Owner:** Fátima  
**Reviewer:** Jefferson  
**Dependencias:** BE-001

## Why

Crear la base relacional de personas, familias, auditoría y estructura académica.

## In Scope

- users, alumnos, padres, docentes y administrativos
- alumno_padre y audit_logs
- periodos, grados, secciones, matrículas, cursos y carga académica

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: users, alumnos, padres, docentes y administrativos, alumno_padre y audit_logs, periodos, grados, secciones, matrículas, cursos y carga académica.

## Source Documents

- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/domain/identity-access.md`
- `../../../../docs/domain/academic.md`
