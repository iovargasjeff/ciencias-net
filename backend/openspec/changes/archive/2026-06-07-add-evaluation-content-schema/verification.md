# Verification: add-evaluation-content-schema

## Automated and Manual Checks

- [x] constraints académicos pasan.
- [x] lectura idempotente.
- [x] índices verificados.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

### Migración

```
docker compose run --rm backend php artisan migrate \
  --path=database/migrations/2026_06_07_000001_create_evaluation_content_tables.php

  2026_06_07_000001_create_evaluation_content_tables ........... 218.38ms DONE
```

### Rollback

```
docker compose run --rm backend php artisan migrate:rollback --step=1

  2026_06_07_000001_create_evaluation_content_tables ............ 50.63ms DONE
```

### Tests (8/8 pasando)

```
docker compose run --rm backend php artisan test tests/Feature/EvaluationContentSchemaTest.php

   PASS  Tests\Feature\EvaluationContentSchemaTest
  ✓ it allows registering a nota for a valid enrollment-examen pair      1.21s
  ✓ it rejects a duplicate nota for the same examen and matricula        0.11s
  ✓ it allows a nota with null puntaje for ausente estado                0.09s
  ✓ it registers a comunicado reading for a user                         0.09s
  ✓ it rejects a duplicate reading for the same comunicado and user      0.09s
  ✓ it rejects a negative puntaje on PostgreSQL via CHECK constraint     0.13s
  ✓ it traverses examen → cargaAcademica → seccion → grado without extra queries 0.11s
  ✓ it creates a notificacion with only created_at                       0.11s

  Tests:    8 passed (12 assertions)
  Duration: 2.06s
```

### Contrato API

La fila en `API_CONTRACTS.md` declara:
> `add-evaluation-content-schema` | Sin contrato HTTP | Persistencia para evaluación, materiales, horarios y comunicaciones | No implementa operaciones HTTP

**Justificación Sin contrato HTTP:** Este change solo crea el esquema de persistencia. Las operaciones HTTP serán implementadas por BE-018..BE-023. No se inventaron endpoints.

### Tablas creadas

| Tabla | Descripción |
|---|---|
| `examenes` | Evaluaciones físicas con estado y canal |
| `notas` | Resultados individuales por matrícula+examen con ranking |
| `reportes_academicos` | Libretas y reportes de academia como archivos privados |
| `materiales` | Recursos pedagógicos por carga académica o generales |
| `horarios` | Bloques horarios por carga académica y día |
| `eventos_calendario` | Eventos del periodo: exámenes, no laborables, etc. |
| `comunicados` | Avisos segmentados por rol/grado con JSONB |
| `comunicado_lecturas` | Registro de lectura único por (comunicado, usuario) |
| `notificaciones` | Notificaciones por panel o correo con estado |

### Constraints y índices verificados

- CHECK `notas_puntaje_no_negativo`: puntaje IS NULL OR puntaje >= 0 ✅
- CHECK `horarios_horas_validas`: hora_fin > hora_inicio ✅
- CHECK `eventos_fechas_validas`: fecha_fin >= fecha_inicio ✅
- UNIQUE `(examen_id, matricula_id)` en notas ✅
- PK compuesta `(comunicado_id, user_id)` en comunicado_lecturas ✅
- Índice parcial `notas_ranking_parcial` para notas con ranking ✅
- Índice parcial `notificaciones_pendientes_idx` para pendientes ✅
- Índice parcial `examenes_publicados_idx` para exámenes activos ✅

### Modelos creados

`Examen`, `Nota`, `ReporteAcademico`, `Material`, `Horario`,
`EventoCalendario`, `Comunicado`, `ComunicadoLectura`, `Notificacion`

### Factories creadas

`ExamenFactory`, `NotaFactory`, `MaterialFactory`,
`ComunicadoFactory`, `NotificacionFactory`
