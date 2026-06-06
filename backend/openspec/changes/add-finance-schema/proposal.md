# Proposal: add-finance-schema

**ID:** DB-003  
**Fase:** Fase 3: Finanzas  
**Owner:** Fátima  
**Reviewer:** Jefferson  
**Dependencias:** DB-001

## Why

Persistir configuración, beneficios, obligaciones y movimientos financieros inmutables.

## In Scope

- configuraciones y conceptos versionados
- beneficios por modalidad y alcance
- obligaciones congeladas y movimientos

## Out of Scope

- Capacidades pertenecientes a otros changes.
- Cambiar reglas compartidas sin actualizar `docs/`.

## Impact

- Proyecto: `backend`.
- Capacidades: configuraciones y conceptos versionados, beneficios por modalidad y alcance, obligaciones congeladas y movimientos.

## Source Documents

- `../../../../docs/domain/finance.md`
- `../../../../docs/architecture/database-schema.md`
- `../../../../docs/decisions/003-finance-history.md`
