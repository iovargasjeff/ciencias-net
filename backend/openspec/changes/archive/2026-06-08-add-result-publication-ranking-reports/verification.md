# Verification: add-result-publication-ranking-reports

## Automated and Manual Checks

- [x] privacidad alumno/padre pasa.
- [x] empates y exclusiones pasan.
- [x] corrección recalcula y notifica.

## Required Evidence

- [x] Resultados de pruebas o comandos adjuntos.
- [x] Escenarios de la delta spec demostrados.
- [x] Permisos negativos y datos sensibles revisados.
- [x] Fila contractual de `../../API_CONTRACTS.md` validada contra OpenAPI y documentos fuente.

## Results

```bash
   PASS  Tests\Feature\AcademicReportsTest
  ✓ publish assessment calculates ranking                                2.26s  
  ✓ list rankings                                                        0.18s  
  ✓ generate report                                                      0.16s  
  ✓ correct result                                                       0.20s  

  Tests:    4 passed
```
