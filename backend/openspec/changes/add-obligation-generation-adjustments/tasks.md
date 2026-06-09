# Tasks: add-obligation-generation-adjustments

## Source of Truth Check

✅ All source documents reviewed and conflicts resolved (see design.md).

## Backend Placement

All code placed under `backend/app/Modules/Finanzas/` with proper layering (Domain, Application, Infrastructure, Presentation).

## Implementation

### Phase 1: Domain Layer & Services

- [x] 1.1 Create `ObligationSnapshot` value object
  - Fields: montos congelados (base, ordinario, pronto_pago, beneficio, descuento)
  - Immutable by design
  - Owner: Fátima

- [x] 1.2 Create `ObligationGenerationService`
  - Method: `generate(concept, studentIds, dueDate): Collection<ObligacionPago>`
  - Resolve single benefit per student
  - Calculate all snapshots per rule
  - Handle transactional integrity
  - Owner: Fátima

- [x] 1.3 Create `ObligationAdjustmentService`
  - Method: `adjust(obligation, adjustmentData, user): ObligacionPago`
  - Validate estado='pendiente'
  - Update only: monto_ordinario, monto_pronto_pago, fecha_limite, beneficio
  - Record before/after audit
  - Dispatch notification event
  - Owner: Fátima

- [x] 1.4 Create `ObligationRepositoryInterface`
  - Methods: getByStudent, getByPeriod, getPendingByStudent, getBulkFiltered
  - Owner: Fátima

### Phase 2: Infrastructure & Data Access

- [x] 2.1 Implement `EloquentObligationRepository`
  - Query optimizations (indexing aware)
  - Lazy loading: avoid N+1
  - Bulk operations support
  - Owner: Fátima

- [x] 2.2 Create idempotency tracking (if needed)
  - Table or cache-based `Idempotency-Key` storage
  - Prevent duplicate obligation generation
  - Owner: Fátima

### Phase 3: Presentation Layer

- [x] 3.1 Create Form Requests (Validation)
  - `GeneratePaymentObligationsRequest`
    - Validate: academic_period_id, concept_id, due_date required
    - Validate: student_ids (if provided) are valid UUIDs
    - Validate: period exists and is active
    - Validate: concept exists and estado='vigente'
    - Permission check: `gestionar_finanzas`
  - `AdjustPaymentObligationRequest`
    - Validate: adjustment_type in [charge, discount, waiver]
    - Validate: amount decimal (10.2)
    - Validate: reason 1-1000 chars
    - Permission check: `gestionar_finanzas`
  - `ListPaymentObligationsRequest`
    - Validate: filters (student_id, concept_id, estado, date range)
    - Permission check: `gestionar_finanzas`
  - `BulkAdjustPaymentObligationRequest`
    - Validate: filters (at least one provided)
    - Validate: adjustment_type, amount, reason
    - Validate: Idempotency-Key present
  - Owner: Fátima

- [x] 3.2 Create API Resources (Serialization)
  - `PaymentObligationResource`
    - Include: id, alumno (name, email), concepto, estado, montos, fechas
    - Follow OpenAPI schema for finance-operations
  - `BulkOperationResultResource`
    - Include: count affected, status, errors (if any)
  - Owner: Fátima

- [x] 3.3 Create `PaymentObligationController`
  - `index(ListPaymentObligationsRequest)` → GET /payment-obligations
    - Paginated list with filters
    - Response: 200 with collection
  - `store(GeneratePaymentObligationsRequest)` → POST /payment-obligations
    - Call GenerationService
    - Response: 202 Accepted with tracking ID (optional)
  - `adjust(string $obligationId, AdjustPaymentObligationRequest)` → POST /payment-obligations/{id}/adjustments
    - Call AdjustmentService
    - Response: 201 Created
  - `bulkAdjust(BulkAdjustPaymentObligationRequest)` → POST /payment-obligations/bulk-adjustments
    - Call BulkAdjustmentService
    - Response: 202 Accepted
  - Owner: Fátima

- [x] 3.4 Register routes in `backend/routes/api.php`
  - Group under `middleware(['auth:sanctum', 'active.account'])`
  - Routes:
    - `GET /payment-obligations` → `index`
    - `POST /payment-obligations` → `store`
    - `POST /payment-obligations/{obligationId}/adjustments` → `adjust`
    - `POST /payment-obligations/bulk-adjustments` → `bulkAdjust`
  - Owner: Fátima

### Phase 4: Testing & Verification

- [x] 4.1 Feature Tests: Obligation Generation
  - ✅ Generate obligations for period creates correct counts
  - ✅ Snapshots are frozen (not modifiable after creation)
  - ✅ All-or-nothing: rollback if any failure mid-generation
  - ✅ Idempotency: same key returns same result without duplicating
  - ✅ User without `gestionar_finanzas` receives 403
  - ✅ Response is 202 Accepted
  - Owner: Fátima

- [x] 4.2 Feature Tests: Obligation Adjustment
  - ✅ Adjust pending obligation updates correctly
  - ✅ Attempt to adjust paid obligation returns 409 Conflict
  - ✅ Attempt to adjust voided obligation returns 409 Conflict
  - ✅ Reason is required and recorded in audit
  - ✅ Before/after values logged
  - ✅ Notification event dispatched
  - ✅ User without `gestionar_finanzas` receives 403
  - Owner: Fátima

- [x] 4.3 Feature Tests: Bulk Adjustment
  - ✅ Apply adjustment to multiple obligations by filters
  - ✅ Concept_id filter works
  - ✅ Grade_id filter works
  - ✅ Section_id filter works
  - ✅ Obligation_ids direct filter works
  - ✅ Idempotency: same key returns same result
  - ✅ Response is 202 Accepted
  - Owner: Fátima

- [x] 4.4 Authorization Tests
  - ✅ User without permission receives 403 on all endpoints
  - ✅ User with permission can access all endpoints
  - Owner: Fátima

- [x] 4.5 Integration Tests
  - ✅ Full flow: generate → list → adjust → verify audit
  - ✅ Snapshots prevent retroactive changes (BE-014 concepts don't affect existing obligations)
  - ✅ Notifications queued correctly
  - Owner: Fátima

- [ ] 4.6 Database & Performance
  - ✅ Run EXPLAIN ANALYZE on complex queries
  - ✅ Verify indexes are used (beneficio_id, estado, alumno_id)
  - ✅ Bulk operations are not N+1
  - Owner: Fátima

- [ ] 4.7 API Contract Validation
  - ✅ Run Scribe and compare output vs OpenAPI
  - ✅ All fields present in responses
  - ✅ Status codes match spec (200, 201, 202, 403, 404, 409, 422)
  - ✅ Error messages are descriptive
  - Owner: Fátima

### Phase 5: Documentation & Archive

- [ ] 5.1 Fill `verification.md` with evidence
  - Test run results
  - Scribe vs OpenAPI diff
  - Database EXPLAIN output
  - Owner: Fátima

- [ ] 5.2 Update Execution Plan
  - Change status: `[ ]` → `[x]`
  - Owner: Fátima (approval by Jefferson)

- [ ] 5.3 Archive this change
  - Move `backend/openspec/changes/add-obligation-generation-adjustments` → `backend/openspec/changes/archive/YYYY-MM-DD-add-obligation-generation-adjustments`
  - Owner: Fátima (approval by Jefferson)

## Estimated Effort

- Phase 1 (Domain): ~4 hours
- Phase 2 (Infrastructure): ~2 hours
- Phase 3 (Presentation): ~6 hours
- Phase 4 (Testing): ~8 hours
- Phase 5 (Documentation): ~1 hour
- **Total: ~21 hours (~3 days)**
