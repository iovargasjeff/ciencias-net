# Design: add-obligation-generation-adjustments

## Source of Truth Check

- Product docs reviewed: ✅ `docs/product/roles-and-permissions.md` - `gestionar_finanzas` permission verified
- Architecture docs reviewed: ✅ `docs/architecture/backend.md` - Module structure pattern confirmed
- API contracts reviewed: ✅ `docs/api/paths/finance-operations.yaml` - All 4 endpoints defined (generate, list, adjust, bulk-adjust)
- Domain docs reviewed: ✅ `docs/domain/finance.md` - Business rules for obligations, benefits, snapshots validated
- Security docs reviewed: ✅ `docs/security/audit-and-operations.md` - Auditing and permission checks confirmed
- Conflicts found: NO - All sources aligned with implementation approach

**Conflict Resolution**: None required. Architecture is consistent with BE-014 patterns, API contracts are complete, and domain rules are clear.

## Backend Placement

All backend domain code placed under modular structure:

```text
backend/app/Modules/Finanzas/
├── Domain/
│   ├── Services/
│   │   ├── ObligationGenerationService.php     [Payment obligation generation with snapshots]
│   │   └── ObligationAdjustmentService.php     [Pending obligation modification with auditing]
│   ├── Repositories/
│   │   └── ObligationRepositoryInterface.php   [Contract for obligation queries]
│   └── ValueObjects/
│       └── ObligationSnapshot.php              [Immutable frozen values at generation]
├── Application/
│   ├── Services/
│   │   └── (use cases pattern via service injection)
│   └── DTOs/
│       ├── GenerateObligationsDTO.php
│       ├── AdjustmentDTO.php
│       └── BulkAdjustmentDTO.php
├── Infrastructure/
│   ├── Models/
│   │   ├── ObligacionPago.php                  [EXISTS - reviewed]
│   │   ├── ConceptoPago.php                    [EXISTS - reviewed]
│   │   └── BeneficioAlumno.php                 [EXISTS - reviewed]
│   └── Repositories/
│       └── EloquentObligationRepository.php    [Eloquent implementation]
└── Presentation/
    ├── Controllers/
    │   └── PaymentObligationController.php     [Generate, list, adjust, bulk operations]
    ├── Requests/
    │   ├── GeneratePaymentObligationsRequest.php
    │   ├── AdjustPaymentObligationRequest.php
    │   ├── ListPaymentObligationsRequest.php
    │   └── BulkAdjustPaymentObligationRequest.php
    └── Resources/
        ├── PaymentObligationResource.php
        └── BulkOperationResultResource.php
```

No domain models/controllers/use cases/policies created under root `app/`.

## Sources and Invariants

- `../../../../docs/domain/finance.md` - Rules: snapshot freezing, immutability of paid/voided, benefit selection, pronto pago mechanics
- `../../../../docs/domain/use-case-catalog.md` - Use cases: obligation generation, adjustment individual/bulk, notification
- `../../../../docs/security/audit-and-operations.md` - Auditing: all modifications logged with motif, user, timestamp
- `../../../../docs/architecture/database-schema.md` (lines 632-703) - Table structure: obligaciones_pago, movimientos_pago, beneficios_alumnos
- `../../../../docs/architecture/backend.md` - Service/Repository pattern, transaction handling, authorization policies
- `../../../../docs/api/paths/finance-operations.yaml` - Endpoint definitions and HTTP contract

## Technical Design

### Obligation Generation (POST /api/v1/payment-obligations)
- **Transactional**: All or nothing via `DB::transaction()`
- **Idempotency**: `Idempotency-Key` header prevents duplicates (stored in cache or table)
- **Snapshot Freezing**:
  - `monto_base_snapshot` ← concept.monto_base
  - `monto_ordinario_snapshot` ← base - benefit_applied
  - `monto_pronto_pago_snapshot` ← ordinary - early_payment_discount
  - `fecha_limite_pronto_pago_snapshot` ← concept.fecha_limite_pronto_pago
  - `monto_beneficio_snapshot` ← amount deducted from base
  - `descuento_pronto_pago_aplicado` ← discount applied
  - All immutable after creation
- **Benefit Resolution**: Single benefit per student (if applicable)
- **Students**: Generate for all students in period OR specified student_ids
- **Validation**: Period active, concept vigente, students enrolled

### Obligation Adjustment (POST /api/v1/payment-obligations/{obligationId}/adjustments)
- **Mutability**: Only estado='pendiente' obligations
- **Fields Adjustable**: monto_ordinario_snapshot, monto_pronto_pago_snapshot, fecha_limite_pronto_pago_snapshot, beneficio_id
- **Motif**: Required (1000 chars max) for auditing
- **Audit Trail**: Before/after values logged
- **Notifications**: Parent/student notified of changes
- **Permission**: Only `gestionar_finanzas`

### Bulk Adjustment (POST /api/v1/payment-obligations/bulk-adjustments)
- **Filters**: obligation_ids[], concept_id, grade_id, section_id
- **Idempotency**: `Idempotency-Key` for bulk operations
- **Async Processing**: Via Laravel Jobs for notification at scale
- **Response**: `202 Accepted` with tracking UUID (optional polling endpoint)

## Security and Authorization

- **Permission Check**: `gestionar_finanzas` verified in controller before action
- **Laravel Authority**: All permission rules enforced in middleware/policies
- **Minimum Privilege**: Only Yanina (user with `gestionar_finanzas`) can generate/adjust
- **Data Protection**: Snapshots prevent retroactive price changes (compliance requirement)
- **Immutability**: Paid/voided obligations cannot be modified (prevent fraud)
- **Audit Trail**: Every adjustment logged: who, when, what changed, why (motif)
- **Notification Privacy**: No sensitive amounts in email subjects; details in portal only

## Testing Strategy

### Unit Tests
- Snapshot calculation: ordinario = base - beneficio, pronto_pago = ordinario - discount
- Benefit selection: single benefit per student, acumulable_pronto_pago logic
- State validation: only pending obligations can be adjusted

### Feature Tests
- **Generation**: Masiva sin parcial (rollback if any fails)
- **Immutability**: Pagada/anulada rechaza ajuste
- **Snapshots**: Congelados no cambian después generación
- **Idempotency**: Mismo Idempotency-Key = mismo resultado
- **Authorization**: Non-`gestionar_finanzas` users receive 403
- **Audit**: Changes recorded with before/after values
- **Notifications**: Correct recipients notified

## Implementation Approach

1. **Domain Services** (obsidian-black box): Pure business logic, no framework awareness
2. **Infrastructure Repository**: Eloquent queries, caching optimization
3. **Application DTOs**: Input/output contracts between layers
4. **Presentation Controller**: HTTP interface, permission checks, resource serialization
5. **Tests**: Feature tests verify full flow, unit tests verify logic

## Rejected Scope

- Payment/receipt recording (BE-016)
- Reporting/reminders (BE-017)
- Concept/benefit configuration (BE-014 - already complete)
- Frontend implementation (FE tickets)
- Partial payment support (V1 rule: only complete payments)
