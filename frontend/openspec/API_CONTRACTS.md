# Contratos API por Change Frontend

Esta matriz es la trazabilidad contractual autoritativa de los changes frontend activos. El frontend consume únicamente
contratos aprobados desde `../../docs/api/openapi.yaml`; no utiliza changes backend activos como contrato estable.

| Change | Relación | Paquetes API | Archivos principales consumidos |
|---|---|---|---|
| `add-account-role-administration` | Consume | `API-IAM` | `paths/identity-access.yaml`, schemas y requests homónimos |
| `add-family-links-administration` | Consume | `API-FAMILY` | `paths/family.yaml`, schemas y requests homónimos |
| `add-academic-structure-administration` | Consume | `API-ACADEMIC` | `paths/academic.yaml`, schemas y requests homónimos |
| `add-family-student-portal-shell` | Consume/agrega navegación | `API-FAMILY`, `API-BIOMETRICS`, `API-STUDENT-ATTENDANCE`, `API-FINANCE-QUERIES`, `API-ACADEMIC-REPORTS`, `API-MATERIALS`, `API-SCHEDULES`, `API-COMMUNICATIONS`, `API-INCIDENTS` | Paths de cada paquete y componentes comunes |
| `add-web-station-activation-capture` | Consume | `API-STATIONS` | `paths/stations.yaml`, schemas y requests homónimos |
| `add-biometric-station-administration` | Consume | `API-BIOMETRICS`, `API-STATIONS` | `paths/biometrics.yaml`, `paths/stations.yaml` y componentes relacionados |
| `add-student-attendance-supervision` | Consume | `API-STUDENT-ATTENDANCE` | `paths/student-attendance.yaml`, schemas y requests homónimos |
| `add-teacher-attendance-payroll` | Consume | `API-TEACHER-ATTENDANCE` | `paths/teacher-attendance.yaml`, schemas y requests homónimos |
| `add-finance-configuration-benefits` | Consume | `API-FINANCE-CONFIG` | `paths/finance-config.yaml`, schemas y requests homónimos |
| `add-obligations-payments-administration` | Consume | `API-FINANCE-OPERATIONS` | `paths/finance-operations.yaml`, schemas y requests homónimos |
| `add-finance-state-portals` | Consume | `API-FINANCE-QUERIES` | `paths/finance-queries.yaml`, schemas y requests homónimos |
| `add-assessment-result-entry` | Consume | `API-ASSESSMENTS` | `paths/assessments.yaml`, schemas y requests homónimos |
| `add-result-publication-reports-portals` | Consume | `API-ACADEMIC-REPORTS` | `paths/academic-reports.yaml`, schemas y requests homónimos |
| `add-materials-portal` | Consume | `API-MATERIALS`, `API-FILES` | `paths/materials.yaml`, `paths/files.yaml` y componentes relacionados |
| `add-schedules-calendar-portals` | Consume | `API-SCHEDULES` | `paths/schedules.yaml`, schemas y requests homónimos |
| `add-communications-notifications` | Consume | `API-COMMUNICATIONS` | `paths/communications.yaml`, schemas y requests homónimos |
| `add-incidents-workflow` | Consume | `API-INCIDENTS`, `API-FILES` | `paths/incidents.yaml`, `paths/files.yaml` y componentes relacionados |
| `add-private-psychology-portal` | Consume | `API-PSYCHOLOGY`, `API-FILES` | `paths/psychology.yaml`, `paths/files.yaml` y componentes relacionados |
| `harden-accessibility-performance` | Verifica transversalmente | `API-CORE` y todos los paquetes consumidos | `openapi.yaml` y componentes comunes |
| `verify-frontend-release-e2e` | Verifica | Todos los paquetes públicos consumidos | `../../docs/api/openapi.yaml` |
| `refine-academic-enrollment-ux` | Consume | `API-IAM`, `API-ACADEMIC`, `API-ASSESSMENTS`, `API-SCHEDULES` | `paths/identity-access.yaml`, `paths/academic.yaml`, `paths/assessments.yaml`, `paths/schedules.yaml` y componentes relacionados |
| `refine-portals-communications-incidents-ux` | Consume | `API-IAM`, `API-FAMILY`, `API-COMMUNICATIONS`, `API-MATERIALS`, `API-FILES`, `API-INCIDENTS`, `API-PSYCHOLOGY`, `API-FINANCE-QUERIES`, `API-STUDENT-ATTENDANCE` | paths y schemas de los paquetes consumidos |

Todos los paths indicados son relativos a `../../docs/api/`.

