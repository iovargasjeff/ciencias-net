# Cobertura Backend

Esta matriz conecta la documentación compartida con los changes que la implementan. Un change debe releer sus fuentes
antes de iniciarse; la matriz permite comprobar que ningún dominio aprobado quede sin backend.

| Área documentada | Changes principales |
|---|---|
| Fundación Laravel, API y CI | `BE-001`, `BE-002`, `OPS-001`, `OPS-002` |
| Autenticación, cuentas, roles y permisos | `DB-001`, `BE-003`, `BE-004` |
| Padres, alumnos y vínculos familiares | `DB-001`, `BE-005` |
| Periodos, grados, secciones, matrículas, cursos y cargas | `DB-001`, `BE-006` |
| Consentimiento, enrolamiento y biometría privada | `DB-002`, `BE-007`, `BE-008` |
| Estaciones web, cámaras y sesiones técnicas | `DB-002`, `BE-009` |
| Asistencia de alumnos, cierre y anomalías | `BE-010`, `BE-011` |
| Asistencia docente, clases y planilla | `BE-012`, `BE-013` |
| Configuración financiera y beneficios | `DB-003`, `BE-014` |
| Obligaciones, ajustes y pagos manuales | `BE-015`, `BE-016` |
| Estado de cuenta, morosos, caja y recordatorios | `BE-017` |
| Evaluaciones y carga de resultados | `DB-004`, `BE-018`, `BE-019` |
| Publicación, ranking y reportes académicos | `BE-020` |
| Materiales privados | `BE-021`, `BE-026` |
| Horarios y calendario | `BE-022` |
| Comunicados y notificaciones | `BE-023` |
| Incidencias y seguimiento TOE | `DB-005`, `BE-024` |
| Psicología confidencial | `DB-005`, `BE-025` |
| Archivos, seguridad, observabilidad y auditoría | `BE-026`, `BE-027` |
| Despliegue, backups y release | `OPS-003`, `BE-028` |

## Documentos fuente obligatorios

- `../../docs/product/approved-requirements.md`
- `../../docs/product/roles-and-permissions.md`
- `../../docs/domain/`
- `../../docs/architecture/database-schema.md`
- `../../docs/architecture/facial-integration.md`
- `../../docs/security/`
