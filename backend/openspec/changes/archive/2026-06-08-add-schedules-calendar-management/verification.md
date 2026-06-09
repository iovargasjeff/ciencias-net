# Verification: add-schedules-calendar-management

## Automated and Manual Checks

- [x] solapamiento inválido rechazado.
- [x] alcance familiar probado.
- [x] día no laboral afecta sesiones.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

```bash
   PASS  Tests\Feature\SchedulesTest
  ✓ superadmin can create schedule                                       3.31s  
  ✓ docente cannot create schedule                                       0.23s  
  ✓ cannot create schedule with overlap same docente                     0.22s  

   PASS  Tests\Feature\CalendarEventsTest
  ✓ superadmin can create calendar event                                 2.98s  
  ✓ can create holiday event                                             0.21s  

  Tests:    5 passed
```
