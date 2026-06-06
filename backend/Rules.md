# Backend Rules

Estas reglas son obligatorias para cualquier cambio backend.

## Arquitectura

- Leer primero `../docs/README.md`, documentos de dominio relacionados y specs aceptadas.
- Usar capacidades nativas de Laravel antes de crear abstracciones propias.
- Mantener arquitectura modular pragmática; no crear capas vacías para CRUD simples.
- Controladores validan/autorizan y llaman casos de uso; no contienen reglas ni consultas complejas.
- Reglas críticas y transacciones pertenecen al backend.
- El servicio facial nunca accede directamente a PostgreSQL ni decide asistencia.

## API

- API versionada bajo `/api/v1`.
- Form Requests para validar entrada y API Resources para salida.
- Policies para acceso al recurso; middleware para permisos generales.
- Listados paginados, filtros explícitos y formato de error estable.
- Scribe/OpenAPI debe actualizarse y verificarse con cambios de contrato.
- Operaciones sensibles o reintentables usan idempotencia.

## Datos

- Migraciones solo en `backend/database/migrations/`.
- La entidad de deuda es `obligaciones_pago`; los pagos reales viven en `movimientos_pago`.
- No modificar históricos pagados/publicados/cerrados; usar movimientos o ajustes auditados.
- Foreign keys, constraints e índices deben acompañar cada esquema.
- Toda operación masiva o financiera usa transacción.
- Verificar consultas importantes con datos representativos y `EXPLAIN ANALYZE`.

## Seguridad y calidad

- Sanctum SPA para humanos; sesiones/credenciales técnicas separadas para estaciones e integraciones.
- Nunca registrar secretos, biometría ni notas psicológicas privadas en logs.
- Agregar pruebas unitarias, integración, feature y autorización según riesgo.
- Un change no termina sin verificación, contrato actualizado y documentación aplicable.

