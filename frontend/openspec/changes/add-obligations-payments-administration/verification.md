# Verification: add-obligations-payments-administration

## Automated and Manual Checks

- [x] pago parcial imposible.
  - La interfaz de `RegisterPaymentForm` no expone un campo para alterar el monto; se impone lógicamente a través de la fecha y del pronto pago.
- [x] acciones históricas confirmadas.
  - La tabla `PaymentsPage` recupera y visualiza la data.
  - La anulación reabre la deuda y pide confirmación expresa.
- [x] upload y errores probados.
  - Subida de archivo mockeada en la UI. 
  - La carga `delay()` en API permite visualizar UI Skeletons.
  - Forzar error dispara el componente visual de error.

## Required Evidence

- [x] Pantallas construidas con React Hook Form, Tailwind CSS, y Zod.
- [x] Todo bajo el rol `gestionar_finanzas`.
