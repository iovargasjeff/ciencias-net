# Design: add-biometric-enrollment-consent

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
