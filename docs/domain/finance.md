# Dominio: Finanzas

## Reglas de negocio

- Los pagos se verifican fuera del sistema y Yanina los registra manualmente.
- Medios permitidos: efectivo, transferencia, Yape, Plin u otro.
- No existe pasarela ni pagos parciales en V1.
- Cada deuda se paga en una única operación por el monto exacto aplicable.
- Una mensualidad puede conservar monto ordinario, monto de pronto pago y fecha límite.
- Ejemplo inicial: S/450 hasta la fecha límite; después S/480.
- Beneficios pueden ser porcentaje, monto fijo o exoneración, con alcance y vigencia.
- Pronto pago no se acumula con otro beneficio salvo configuración expresa.
- Solo la cuenta específica con `gestionar_finanzas` modifica configuración y deudas pendientes.
- Puede ajustar montos, fechas, vencimiento o beneficio de pendientes, individual o masivamente.
- Todo ajuste exige motivo, auditoría y notificación.
- Deudas pagadas/anuladas y movimientos históricos son inmutables.
- Correcciones usan anulación, devolución o nuevo pago completo.

## Casos de uso compartidos

- Configurar conceptos, montos, generación, vencimientos y pronto pago.
- Configurar beneficio.
- Generar deudas.
- Ajustar deuda pendiente individual o masivamente.
- Registrar pago completo y generar recibo.
- Registrar anulación o devolución.
- Consultar estado de cuenta, morosos y reporte de caja.

La entidad que representa la deuda se denomina definitivamente `obligaciones_pago`. Los pagos reales se registran en
`movimientos_pago`.
