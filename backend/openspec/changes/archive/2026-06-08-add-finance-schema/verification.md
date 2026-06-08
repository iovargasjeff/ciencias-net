# Verification: add-finance-schema

## Automated and Manual Checks

- [x] constraints de montos pasan.
- [x] referencias duplicadas fallan.
- [x] rollback completo.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

- Inicio: `DB-003` desbloqueado porque `DB-001` está `[x]` en `backend/openspec/EXECUTION_PLAN.md`.
- Contrato: la fila `add-finance-schema` en `backend/openspec/API_CONTRACTS.md` declara `Sin contrato HTTP`; este change no crea endpoints y solo agrega persistencia para `API-FINANCE-*`.
- Implementación: agregada migración financiera, modelos Eloquent mínimos bajo `app/Modules/Finanzas/Infrastructure/Models`, relaciones de navegación legacy necesarias y `FinanceSchemaTest`.
- Arquitectura modular: `docker compose exec backend composer guard:architecture` pasó con `Architecture guard passed`.
- Pruebas DB-003: `docker compose exec backend php artisan test --filter=FinanceSchemaTest` pasó con `7 passed (16 assertions)`.
- Escenarios demostrados: snapshots inmutables de obligación, unicidad de referencia por medio, efectivo sin referencia, constraints de montos, beneficio inválido y mensualidad sin mes.
- Documentación/contrato: no se modificó OpenAPI porque el change es `Sin contrato HTTP`; la evidencia quedó registrada para revisión.
