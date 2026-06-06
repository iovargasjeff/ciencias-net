# Proposal: initialize-docker-development

**ID:** OPS-001  
**Fase:** Fase 0: Fundación ejecutable  
**Owner:** André  
**Reviewer:** Jefferson  
**Dependencias:** Ninguna

## Why

Permitir levantar todo el entorno de forma reproducible.

## In Scope

- Compose para frontend, backend, PostgreSQL, worker y facial-service
- red privada, volúmenes y healthchecks
- guía de primer arranque

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: Compose para frontend, backend, PostgreSQL, worker y facial-service, red privada, volúmenes y healthchecks, guía de primer arranque.

## Source Documents

- `../../../../docs/architecture/deployment.md`
- `../../../../docs/architecture/deployment-runbook.md`
- `../../../../docs/architecture/detailed-system-design.md`
