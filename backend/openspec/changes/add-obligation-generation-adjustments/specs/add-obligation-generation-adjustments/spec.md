# add-obligation-generation-adjustments Specification

## Purpose

Generar obligaciones (deudas) y ajustar obligaciones pendientes con auditoría y notificación.

## ADDED Requirements

### Requirement: 1 - Obligation Generation

Generar obligación SHALL congelar beneficio y montos aplicables

#### Scenario: 1.1 - Cada deuda conserva snapshot

- GIVEN existe un concepto vigente con monto_base=500, descuento_pronto_pago=50, fecha_limite_pronto_pago=15 dias
- AND existe beneficio aplicable al alumno con modalidad='porcentaje', valor=10%
- WHEN se genera deuda para el alumno
- THEN se crea obligacion con:
  - `monto_base_snapshot` = 500
  - `monto_beneficio_snapshot` = 50 (10% of 500)
  - `monto_ordinario_snapshot` = 450 (500 - 50)
  - `monto_pronto_pago_snapshot` = 400 (450 - 50 discount)
  - `descuento_pronto_pago_aplicado` = 50
  - `fecha_limite_pronto_pago_snapshot` = concept.fecha_limite
  - All snapshots are immutable after creation

#### Scenario: 1.2 - Generación masiva es transaccional

- GIVEN hay 100 alumnos a generar
- AND 1 alumno tiene datos inválidos
- WHEN se intenta generar para todos
- THEN rollback total (ningún alumno tiene obligación creada)

#### Scenario: 1.3 - Generación es idempotente

- GIVEN se genera deuda con Idempotency-Key=XYZ
- WHEN se repite petición con mismo Idempotency-Key
- THEN retorna 202 Accepted
- AND no se crean obligaciones duplicadas
- AND retorna mismos IDs de obligaciones

#### Scenario: 1.4 - Deuda solo para estudiantes enrolados

- GIVEN concepto de tipo='mensualidad' para periodo 2026-I
- WHEN se genera para estudiantes enrolados en secciones activas de periodo
- THEN solo crea obligaciones para alumnos con matricula estado='activo'

### Requirement: 2 - Obligation Immutability

Una deuda pagada o anulada SHALL ser inmutable

#### Scenario: 2.1 - Deuda pagada rechaza ajuste

- GIVEN existe deuda con estado='pagado'
- WHEN Yanina intenta ajustar el monto
- THEN retorna 409 Conflict
- AND deuda no cambia

#### Scenario: 2.2 - Deuda anulada rechaza ajuste

- GIVEN existe deuda con estado='vencido' (ej. por anulación)
- WHEN Yanina intenta ajustar fecha límite
- THEN retorna 409 Conflict

### Requirement: 3 - Obligation Adjustment

Ajuste a deuda pendiente SHALL requerir motivo y registrar auditoría

#### Scenario: 3.1 - Ajuste individual con motivo

- GIVEN existe deuda con estado='pendiente'
- WHEN Yanina ajusta monto_ordinario_snapshot=500 con reason='Descuento por beca aprobada'
- THEN deuda se actualiza
- AND audit_logs registra: usuario, before={monto: 450}, after={monto: 500}, motivo='...'
- AND notificación enviada a padre/alumno

#### Scenario: 3.2 - Ajuste a múltiples deudas por concepto

- GIVEN existen 50 deudas de concepto X en estado='pendiente'
- AND Yanina especifica filter.concept_id=X
- WHEN aplica bulk adjustment con amount=50, adjustment_type='discount'
- THEN todas 50 deudas reciben descuento de 50
- AND cada una registra auditoría
- AND respuesta 202 Accepted

#### Scenario: 3.3 - Solo gestionar_finanzas puede ajustar

- GIVEN usuario sin permiso `gestionar_finanzas`
- WHEN intenta ajustar deuda
- THEN retorna 403 Forbidden

### Requirement: 4 - Benefit Application

Beneficio único por deuda SHALL ser resuelto al generar

#### Scenario: 4.1 - Un beneficio por alumno y concepto

- GIVEN alumno tiene 2 beneficios vigentes (beca 20%, descuento 100 soles)
- AND concepto aplica a ambos
- WHEN se genera deuda
- THEN Yanina selecciona 1 beneficio (UI logic en FE, backend almacena seleccionado)
- AND deuda conserva beneficio_id del aplicado

#### Scenario: 4.2 - Acumulación pronto pago

- GIVEN beneficio con acumulable_pronto_pago=true
- WHEN se genera deuda con pronto pago vigente
- THEN monto_pronto_pago = (ordinario con beneficio) - descuento_pronto_pago
- IF acumulable_pronto_pago=false
- THEN no se acumula el descuento

### Requirement: 5 - Listing and Filtering

Listado de deudas SHALL soportar filtros por estudiante, concepto, estado, fecha

#### Scenario: 5.1 - Listar deudas pendientes de alumno

- GIVEN alumno X tiene 3 deudas (2 pendientes, 1 pagada)
- WHEN GET /payment-obligations?student_id=X&estado=pendiente
- THEN retorna 2 deudas paginadas
- AND incluye: id, alumno, concepto, montos, fechas, estado

#### Scenario: 5.2 - Listar por rango de fecha

- GIVEN múltiples deudas con vencimientos en junio y julio
- WHEN GET /payment-obligations?due_date_from=2026-06-01&due_date_to=2026-06-30
- THEN retorna solo deudas de junio

## MODIFIED Requirements

Ninguno (esta es primera iteración).

## CONSTRAINTS

- Deudas pagadas/anuladas son inmutables (protege auditoría y compliance)
- Snapshots congelados impiden retroactivas de precios (BE-014 cambios no afectan deudas existentes)
- Un solo beneficio por deuda (simplifica lógica de cálculo)
- Pronto pago no acumula con beneficio (excepto si flag acumulable_pronto_pago=true)
- Solo usuario con `gestionar_finanzas` puede generar/ajustar

## ACCEPTANCE CRITERIA

✅ Generación transaccional (todo o nada)
✅ Snapshots congelados inmutables
✅ Auditoría registrada antes/después de ajustes
✅ Notificaciones despachadas correctamente
✅ Permisos enforced (403 si no gestionar_finanzas)
✅ Idempotencia funcional (mismo key, mismo resultado)
✅ Todas las pruebas verdes
✅ Scribe vs OpenAPI sin conflictos

