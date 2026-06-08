# Design: add-biometric-enrollment-consent

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


## Sources and Invariants

- `../../../../docs/security/data-and-files.md`
- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/architecture/database-schema.md`

## Technical Design

- Crear casos de uso de consentimiento/enrolamiento.
- Integrar storage R2 privado.
- Cifrar embeddings con clave separada.
- Auditar y publicar endpoints.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- enrolamiento sin consentimiento bloqueado.
- revocación desactiva.
- objetos privados no son públicos.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
