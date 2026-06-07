# Backend Execution Plan

## Leyenda

- `[ ]` pendiente
- `[~]` en progreso
- `[-]` bloqueado
- `[x]` terminado, verificado y archivado

## Fase 0: Fundación ejecutable

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| BE-001 | `[x]` | `initialize-backend-foundation` | Jefferson | André | Ninguna |
| OPS-001 | `[x]` | `initialize-docker-development` | André | Jefferson | Ninguna |
| BE-002 | `[x]` | `define-api-contract-conventions` | Jefferson | André | BE-001 |
| OPS-002 | `[x]` | `configure-backend-quality-ci` | André | Jefferson | BE-001, OPS-001 |

## Fase 1: Identidad y estructura académica

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-001 | `[x]` | `add-identity-academic-schema` | Jefferson | André | BE-001 |
| BE-003 | `[x]` | `add-human-authentication` | Jefferson | André | BE-002, DB-001 |
| BE-004 | `[x]` | `add-roles-permissions-account-management` | Jefferson | André | BE-003 |
| BE-005 | `[x]` | `add-family-links-management` | Jefferson | André | BE-004 |
| BE-006 | `[x]` | `add-academic-structure-management` | Jefferson | André | DB-001, BE-004 |

## Fase 2: Facial y asistencia

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-002 | `[~]` | `add-biometric-attendance-schema` | André | Jefferson | DB-001 |
| BE-007 | `[~]` | `add-biometric-enrollment-consent` | André | Jefferson | DB-002 |
| BE-008 | `[~]` | `add-facial-service-integration` | André | Jefferson | OPS-001, DB-002 |
| BE-009 | `[~]` | `add-web-station-management` | André | Jefferson | BE-004, DB-002, BE-008 |
| BE-010 | `[~]` | `add-student-attendance-events` | André | Jefferson | BE-008, BE-009 |
| BE-011 | `[~]` | `add-student-attendance-closure-review` | André | Jefferson | BE-010 |
| BE-012 | `[~]` | `add-teacher-attendance-sessions` | André | Jefferson | BE-006, BE-010 |
| BE-013 | `[~]` | `add-teacher-payroll-liquidation` | André | Jefferson | BE-012 |

## Fase 3: Finanzas

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-003 | `[ ]` | `add-finance-schema` | Fátima | Jefferson | DB-001 |
| BE-014 | `[ ]` | `add-finance-configuration-benefits` | Fátima | Jefferson | BE-004, DB-003 |
| BE-015 | `[ ]` | `add-obligation-generation-adjustments` | Fátima | Jefferson | BE-014 |
| BE-016 | `[ ]` | `add-payment-movements-receipts` | Fátima | Jefferson | BE-015 |
| BE-017 | `[ ]` | `add-finance-queries-reminders` | Fátima | Jefferson | BE-016 |

## Fase 4: Evaluación y contenido

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-004 | `[x]` | `add-evaluation-content-schema` | Jefferson | André | DB-001 |
| BE-018 | `[x]` | `add-assessment-management` | Jefferson | André | BE-006, DB-004 |
| BE-019 | `[ ]` | `add-result-entry-import` | Jefferson | André | BE-018 |
| BE-020 | `[ ]` | `add-result-publication-ranking-reports` | Jefferson | André | BE-019 |
| BE-021 | `[ ]` | `add-materials-management` | Jefferson | André | BE-006, DB-004 |
| BE-022 | `[ ]` | `add-schedules-calendar-management` | Jefferson | André | BE-006, DB-004 |
| BE-023 | `[ ]` | `add-communications-notifications` | Jefferson | André | BE-004, DB-004 |

## Fase 5: Incidencias y Psicología

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| DB-005 | `[ ]` | `add-incidents-psychology-schema` | Fátima | André | DB-001 |
| BE-024 | `[ ]` | `add-incidents-workflow` | Fátima | André | BE-004, DB-005 |
| BE-025 | `[ ]` | `add-private-psychology-workflow` | Fátima | André | BE-024 |

## Fase 6: Operación y release

| ID | Status | Change | Owner | Reviewer | Dependencies |
|---|---|---|---|---|---|
| BE-026 | `[ ]` | `add-private-files-service` | André | Jefferson | BE-001 |
| BE-027 | `[ ]` | `harden-security-observability` | André | Jefferson | BE-003, BE-026 |
| OPS-003 | `[ ]` | `add-production-deployment-backups` | André | Jefferson | OPS-002, BE-008 |
| BE-028 | `[ ]` | `verify-backend-release` | André | Jefferson | BE-013, BE-017, BE-020, BE-023, BE-025, BE-027, OPS-003 |

## Regla de ejecución

Cada change se detalla desde sus documentos fuente y su fila en `API_CONTRACTS.md` antes de implementarse. No se inicia
un change bloqueado, con contrato requerido sin aprobar, ni se archiva sin demostrar todos sus escenarios.
