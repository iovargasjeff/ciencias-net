# Verification: add-finance-queries-reminders

## Automated and Manual Checks

- [x] alcance familiar probado.
- [x] valores cambian al vencer fecha.
- [x] EXPLAIN de morosos/caja revisado.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

Todos los escenarios de la especificación fueron validados mediante pruebas automatizadas con éxito.

### Pruebas de Pest Ejecutadas:
```text
   PASS  Tests\Feature\Modules\Finanzas\FinanceQueryTest
  ✓ student can view own account statements
  ✓ parent can view linked student account statements
  ✓ parent cannot view unlinked student account statements
  ✓ admin can view any account statement
  ✓ shows early payment amount before deadline and ordinary after
  ✓ admin can list debtors
  ✓ admin can get cash report
  ✓ admin can send reminders and idempotency
  ✓ job creates notifications and emails

  Tests:    9 passed (29 assertions)
  Duration: 15.27s
```

### Resultados de EXPLAIN para Consultas de Morosos y Caja:
```text
--- EXPLAIN MOROSOS (Alumno has overdue obligations) ---
Nested Loop Semi Join  (cost=0.00..0.01 rows=1 width=718)
  Join Filter: (alumnos.id = obligaciones_pago.alumno_id)
  ->  Seq Scan on alumnos  (cost=0.00..0.00 rows=1 width=718)
  ->  Seq Scan on obligaciones_pago  (cost=0.00..0.00 rows=1 width=16)
        Filter: (((estado)::text = ANY ('{pendiente,vencido}'::text[])) AND (fecha_vencimiento < '2026-06-08'::date))

--- EXPLAIN CAJA (Movimientos de pago in range) ---
Seq Scan on movimientos_pago  (cost=0.00..10.90 rows=1 width=1198)
  Filter: ((created_at >= '2026-06-08 00:00:00+00'::timestamp with time zone) AND (created_at <= '2026-06-08 23:59:59+00'::timestamp with time zone))
```
La consulta de morosos hace uso del filtro sobre `estado IN ('pendiente','vencido')` y `fecha_vencimiento`, lo cual califica de forma directa para el índice parcial `obligaciones_pago_pendientes_idx` en PostgreSQL en cuanto la base de datos comience a crecer.
