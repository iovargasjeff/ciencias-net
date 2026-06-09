# Design: add-biometric-station-administration

## Sources and Invariants

- `../../../../docs/architecture/facial-integration.md`
- `../../../../docs/security/data-and-files.md`
- `../../../../docs/product/roles-and-permissions.md`

## Technical Design

- Crear panel biométrico y de dispositivos.
- Crear flujo guiado de consentimiento/enrolamiento.
- Crear gestión de estación/cámaras y activación.
- Evitar mostrar embeddings o URLs permanentes.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- enrolamiento sin consentimiento bloqueado.
- revocación E2E.
- datos biométricos no aparecen en UI/consola.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
