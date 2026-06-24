# Contratos API por Change Backend

Esta matriz es la trazabilidad contractual autoritativa de los changes backend activos. El contrato design-first vive en
`../../docs/api/`; Scribe y `backend/public/docs/openapi.yaml` son artefactos generados para comparar la implementación.

Un paquete listado aquí todavía requiere aprobación explícita antes de iniciar su implementación. Los changes `DB-*` y
`OPS-*` sin comportamiento HTTP declaran `Sin contrato HTTP`, pero deben respetar las reglas de dominio y seguridad.

| Change | Relación | Paquetes API | Archivos propietarios |
|---|---|---|---|
| `add-roles-permissions-account-management` | Implementa | `API-IAM` | `paths/identity-access.yaml`, `request-bodies/identity-access.yaml`, `schemas/identity-access.yaml` |
| `add-family-links-management` | Implementa | `API-FAMILY` | `paths/family.yaml`, `request-bodies/family.yaml`, `schemas/family.yaml` |
| `add-academic-structure-management` | Implementa | `API-ACADEMIC` | `paths/academic.yaml`, `request-bodies/academic.yaml`, `schemas/academic.yaml` |
| `add-biometric-attendance-schema` | Sin contrato HTTP | Persistencia para `API-BIOMETRICS`, `API-STATIONS`, `API-STUDENT-ATTENDANCE` | No implementa operaciones HTTP |
| `add-biometric-enrollment-consent` | Implementa | `API-BIOMETRICS` | `paths/biometrics.yaml`, `request-bodies/biometrics.yaml`, `schemas/biometrics.yaml` |
| `add-facial-service-integration` | Implementa | `API-FACIAL-INTERNAL` | `internal/facial-openapi.yaml` |
| `add-web-station-management` | Implementa | `API-STATIONS` | `paths/stations.yaml`, `request-bodies/stations.yaml`, `schemas/stations.yaml` |
| `add-student-attendance-events` | Implementa | `API-STUDENT-ATTENDANCE` | `paths/student-attendance.yaml`, `request-bodies/student-attendance.yaml`, `schemas/student-attendance.yaml` |
| `add-student-attendance-closure-review` | Implementa | `API-STUDENT-ATTENDANCE` | `paths/student-attendance.yaml`, `request-bodies/student-attendance.yaml`, `schemas/student-attendance.yaml` |
| `add-teacher-attendance-sessions` | Implementa | `API-TEACHER-ATTENDANCE` | `paths/teacher-attendance.yaml`, `request-bodies/teacher-attendance.yaml`, `schemas/teacher-attendance.yaml` |
| `add-teacher-payroll-liquidation` | Implementa | `API-TEACHER-ATTENDANCE` | `paths/teacher-attendance.yaml`, `request-bodies/teacher-attendance.yaml`, `schemas/teacher-attendance.yaml` |
| `add-finance-schema` | Sin contrato HTTP | Persistencia para `API-FINANCE-*` | No implementa operaciones HTTP |
| `add-finance-configuration-benefits` | Implementa | `API-FINANCE-CONFIG` | `paths/finance-config.yaml`, `request-bodies/finance-config.yaml`, `schemas/finance-config.yaml` |
| `add-obligation-generation-adjustments` | Implementa | `API-FINANCE-OPERATIONS` | `paths/finance-operations.yaml`, `request-bodies/finance-operations.yaml`, `schemas/finance-operations.yaml` |
| `add-payment-movements-receipts` | Implementa | `API-FINANCE-OPERATIONS` | `paths/finance-operations.yaml`, `request-bodies/finance-operations.yaml`, `schemas/finance-operations.yaml` |
| `add-finance-queries-reminders` | Implementa | `API-FINANCE-QUERIES` | `paths/finance-queries.yaml`, `request-bodies/finance-queries.yaml`, `schemas/finance-queries.yaml` |
| `add-evaluation-content-schema` | Sin contrato HTTP | Persistencia para evaluación, materiales, horarios y comunicaciones | No implementa operaciones HTTP |
| `add-assessment-management` | Implementa | `API-ASSESSMENTS` | `paths/assessments.yaml`, `request-bodies/assessments.yaml`, `schemas/assessments.yaml` |
| `add-result-entry-import` | Implementa | `API-ASSESSMENTS` | `paths/assessments.yaml`, `request-bodies/assessments.yaml`, `schemas/assessments.yaml` |
| `add-result-publication-ranking-reports` | Implementa | `API-ACADEMIC-REPORTS` | `paths/academic-reports.yaml`, `request-bodies/academic-reports.yaml`, `schemas/academic-reports.yaml` |
| `add-materials-management` | Implementa | `API-MATERIALS` | `paths/materials.yaml`, `request-bodies/materials.yaml`, `schemas/materials.yaml` |
| `add-schedules-calendar-management` | Implementa | `API-SCHEDULES` | `paths/schedules.yaml`, `request-bodies/schedules.yaml`, `schemas/schedules.yaml` |
| `add-communications-notifications` | Implementa | `API-COMMUNICATIONS` | `paths/communications.yaml`, `request-bodies/communications.yaml`, `schemas/communications.yaml` |
| `add-incidents-psychology-schema` | Sin contrato HTTP | Persistencia para `API-INCIDENTS`, `API-PSYCHOLOGY` | No implementa operaciones HTTP |
| `add-incidents-workflow` | Implementa | `API-INCIDENTS` | `paths/incidents.yaml`, `request-bodies/incidents.yaml`, `schemas/incidents.yaml` |
| `add-private-psychology-workflow` | Implementa | `API-PSYCHOLOGY` | `paths/psychology.yaml`, `request-bodies/psychology.yaml`, `schemas/psychology.yaml` |
| `add-private-files-service` | Implementa | `API-FILES` | `paths/files.yaml`, `request-bodies/files.yaml`, `schemas/files.yaml` |
| `harden-security-observability` | Modifica/verifica transversalmente | `API-CORE` y seguridad común | `openapi.yaml`, `parameters/common.yaml`, `responses/common.yaml`, `schemas/common.yaml`, `security-schemes/common.yaml` |
| `add-production-deployment-backups` | Sin contrato HTTP | Operación y despliegue | No implementa operaciones HTTP nuevas |
| `verify-backend-release` | Verifica | Todos los paquetes públicos e internos | `../../docs/api/openapi.yaml`, `../../docs/api/internal/facial-openapi.yaml` |
| `refine-academic-enrollment-rules` | Modifica | `API-ACADEMIC`, `API-ASSESSMENTS`, `API-SCHEDULES` | `paths/academic.yaml`, `paths/assessments.yaml`, `paths/schedules.yaml`, request-bodies y schemas homonimos |
| `refine-identity-family-role-rules` | Modifica | `API-IAM`, `API-FAMILY`, `API-COMMUNICATIONS`, `API-FINANCE-QUERIES`, `API-INCIDENTS`, `API-PSYCHOLOGY` | `paths/identity-access.yaml`, `paths/family.yaml`, `paths/communications.yaml`, `paths/finance-queries.yaml`, `paths/incidents.yaml`, `paths/psychology.yaml`, request-bodies y schemas homonimos |

Todos los paths indicados son relativos a `../../docs/api/`.
