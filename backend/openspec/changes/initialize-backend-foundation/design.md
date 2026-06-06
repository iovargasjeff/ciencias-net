# Design: initialize-backend-foundation

## Sources and Invariants

- `../../../../docs/architecture/backend.md`
- `../../../../docs/decisions/005-technical-foundation.md`

## Technical Design

- Crear proyecto Laravel y módulos base.
- Configurar PostgreSQL, colas, correo y storage privado en .env.example.
- Instalar dependencias y healthcheck.
- Agregar pruebas de arranque.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- composer install y Pest pasan.
- migraciones base aplican y revierten.
- healthcheck no expone configuración.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
