# Verification: add-incidents-workflow

## 1. Test Execution

**Command:**
`docker compose exec backend php artisan test --filter IncidentWorkflowTest`

**Output:**
```
   PASS  Tests\Feature\IncidentWorkflowTest
  ✓ auxiliar can create incident                                         2.54s  
  ✓ parent cannot view incidents                                         0.20s  
  ✓ toe can transition and add follow up                                 0.27s  

  Tests:    3 passed (6 assertions)
```

## 2. API Contract Adherence

- Se mapearon enums de API (`low`, `critical`, `open`, `resolved`) a enums de DB (`leve`, `grave`, `abierto`, `resuelto`) garantizando que ambas especificaciones converjan sin conflictos.
- Se implementaron los 5 endpoints requeridos en la especificación (`GET /incidents`, `POST /incidents`, `POST /incidents/{id}/transitions`, `POST /incidents/{id}/follow-ups`, `POST /incidents/reports`).
- El Request Validation (`FormRequest`) respeta `additionalProperties: false` y restringe la longitud y formato de los campos según la spec (`min:1`, `max:5000`).
- Roles validados a través de `IncidentPolicy` y Laravel Gate respetando `x-roles`.

## 3. Security and Requirements

- El `IncidentPolicy` bloquea el acceso de listar incidencias al rol de `padre`, tal cual está diseñado en las matrices de permisos (los padres se enteran por notificaciones, no iterando endpoints).
- Los seguimientos (`follow-ups`) son registrados por `superadmin` o `toe`.
- El historial es inmutable: Los cambios de estado no mutan el log pasado sino que añaden una nueva entrada.
