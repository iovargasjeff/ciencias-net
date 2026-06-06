# Design: initialize-vite-frontend-foundation

## Sources and Invariants

- `../../../../docs/architecture/frontend.md`
- `../../../../docs/decisions/004-frontend-foundation.md`
- `../../../../docs/decisions/005-technical-foundation.md`

## Technical Design

- Crear proyecto y estructura por features.
- Instalar dependencias aprobadas.
- Configurar alias, env tipado y página base.
- Agregar smoke tests.

## Security and Authorization

- Laravel sigue siendo autoridad de permisos y reglas críticas.
- Aplicar mínimo privilegio, auditoría y protección de datos según los documentos fuente.

## Testing Strategy

- install/build/test pasan.
- Phosphor Icons es la única librería de iconos.
- arranque no muestra errores.

## Rejected Scope

- No implementar capacidades declaradas en otros changes.
- No depender de changes activos de otro proyecto como contrato estable.
