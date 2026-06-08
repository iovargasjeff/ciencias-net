# Backend Execution Plan

## Leyenda

- `[ ]` pendiente
- `[~]` en progreso
- `[-]` bloqueado
- `[x]` terminado, verificado y archivado

## Fase 0: Fundación ejecutable

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| BE-001 | `[x]` | `initialize-backend-foundation` | Jefferson | Fátima | Ninguna |
| OPS-001 | `[x]` | `initialize-docker-development` | André | Jefferson | Ninguna |
| BE-002 | `[x]` | `define-api-contract-conventions` | Jefferson | Fátima | BE-001 |
| OPS-002 | `[x]` | `configure-backend-quality-ci` | André | Jefferson | BE-001, OPS-001 |

## Fase 1: Identidad y estructura académica

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-001 | `[x]` | `add-identity-academic-schema` | Fátima | Jefferson | BE-001 |
| BE-003 | `[x]` | `add-human-authentication` | Jefferson | Fátima | BE-002, DB-001 |
| BE-004 | `[x]` | `add-roles-permissions-account-management` | Jefferson | Fátima | BE-003 |
| BE-005 | `[x]` | `add-family-links-management` | Fátima | Jefferson | BE-004 |
| BE-006 | `[x]` | `add-academic-structure-management` | Fátima | Jefferson | DB-001, BE-004 |

## Fase 2: Facial y asistencia

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-002 | `[x]` | `add-biometric-attendance-schema` | Fátima | Jefferson | DB-001 |
| BE-007 | `[x]` | `add-biometric-enrollment-consent` | Jefferson | Fátima | DB-002 |
| BE-008 | `[x]` | `add-facial-service-integration` | André | Jefferson | OPS-001, DB-002 |
| BE-009 | `[x]` | `add-web-station-management` | André | Jefferson | BE-004, DB-002, BE-008 |
| BE-010 | `[x]` | `add-student-attendance-events` | Jefferson | Fátima | BE-008, BE-009 |
| BE-011 | `[x]` | `add-student-attendance-closure-review` | Jefferson | Fátima | BE-010 |
| BE-012 | `[x]` | `add-teacher-attendance-sessions` | Fátima | Jefferson | BE-006, BE-010 |
| BE-013 | `[x]` | `add-teacher-payroll-liquidation` | Fátima | Jefferson | BE-012 |

## Fase 3: Finanzas

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-003 | `[x]` | `add-finance-schema` | Fátima | Jefferson | DB-001 |
| BE-014 | `[ ]` | `add-finance-configuration-benefits` | Jefferson | Fátima | BE-004, DB-003 |
| BE-015 | `[ ]` | `add-obligation-generation-adjustments` | Jefferson | Fátima | BE-014 |
| BE-016 | `[ ]` | `add-payment-movements-receipts` | Jefferson | Fátima | BE-015 |
| BE-017 | `[ ]` | `add-finance-queries-reminders` | André | Jefferson | BE-016 |

## Fase 4: Evaluación y contenido

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-004 | `[x]` | `add-evaluation-content-schema` | Fátima | Jefferson | DB-001 |
| BE-018 | `[x]` | `add-assessment-management` | Jefferson | Fátima | BE-006, DB-004 |
| BE-019 | `[ ]` | `add-result-entry-import` | Jefferson | Fátima | BE-018 |
| BE-020 | `[ ]` | `add-result-publication-ranking-reports` | Jefferson | Fátima | BE-019 |
| BE-021 | `[ ]` | `add-materials-management` | André | Jefferson | BE-006, DB-004 |
| BE-022 | `[ ]` | `add-schedules-calendar-management` | André | Jefferson | BE-006, DB-004 |
| BE-023 | `[ ]` | `add-communications-notifications` | André | Jefferson | BE-004, DB-004 |

## Fase 5: Incidencias y Psicología

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-005 | `[ ]` | `add-incidents-psychology-schema` | Fátima | Jefferson | DB-001 |
| BE-024 | `[ ]` | `add-incidents-workflow` | Jefferson | Fátima | BE-004, DB-005 |
| BE-025 | `[ ]` | `add-private-psychology-workflow` | Jefferson | Fátima | BE-024 |

## Fase 6: Operación y release

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| BE-026 | `[ ]` | `add-private-files-service` | André | Fátima | BE-001 |
| BE-027 | `[ ]` | `harden-security-observability` | André | Fátima | BE-003, BE-026 |
| OPS-003 | `[ ]` | `add-production-deployment-backups` | André | Jefferson | OPS-002, BE-008 |
| BE-028 | `[ ]` | `verify-backend-release` | Jefferson | André | BE-013, BE-017, BE-020, BE-023, BE-025, BE-027, OPS-003 |

## Regla de ejecución

Cada change se detalla desde sus documentos fuente antes de implementarse. No se inicia un change bloqueado ni se archiva sin demostrar todos sus escenarios.
