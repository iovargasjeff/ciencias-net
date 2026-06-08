# Tasks: add-payment-movements-receipts

## Source of Truth Check

- Product docs reviewed:
- Architecture docs reviewed:
- API contracts reviewed:
- Domain docs reviewed:
- Security docs reviewed:
- Conflicts found: yes/no

If any conflict exists, do not implement until docs are corrected or the task is rewritten.

## Backend Placement

All backend domain code must be placed under:

```text
backend/app/Modules/<ModuleName>/
├── Domain/
├── Application/
├── Infrastructure/
└── Presentation/
```

No domain models/controllers/use cases/policies may be created under root `app/`.


## Implementation
- [ ] 1.1 Implementar registro de pago transaccional. Owner: Jefferson
- [ ] 1.2 Calcular monto exigible por fecha. Owner: Jefferson
- [ ] 1.3 Generar recibo PDF y guardar comprobante privado. Owner: Jefferson
- [ ] 1.4 Implementar anulación/devolución. Owner: Jefferson

## Verification
- [ ] 2.1 Verificar que pagos parciales rechazados. Owner: Jefferson
- [ ] 2.2 Verificar que referencia duplicada rechazada. Owner: Jefferson
- [ ] 2.3 Verificar que historial permanece inmutable. Owner: Jefferson

## Review and Archive
- [ ] 3.1 Publicar contratos/documentación afectados. Owner: Jefferson
- [ ] 3.2 Revisar y archivar la spec aceptada. Reviewer: Jefferson
