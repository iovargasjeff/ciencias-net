# Modelo de Negocio — CienciasNET

## Modelo Definido: Pago Único + Mantenimiento Mensual

CienciasNET se entrega bajo **licencia única con soporte continuo**. El cliente paga una sola vez por el desarrollo y luego abona una cuota mensual por la infraestructura y el soporte.

---

## Pago Único — Desarrollo e Implementación

| Incluye | Detalle |
|---|---|
| Desarrollo del sistema | Todos los módulos acordados |
| Configuración del VPS | Nginx, PHP-FPM, PostgreSQL 16, PM2, SSL |
| Configuración de almacenamiento | Carpetas del VPS + Intervention Image |
| Migración de datos | Importación desde Excel u otros formatos |
| Capacitación del personal | Sesión para admin, docentes y coordinadores |
| Manual de usuario | PDF por cada rol |

**Rango de precio: S/. 5,000 – S/. 8,000**

---

## Mantenimiento Mensual

| Incluye | Detalle |
|---|---|
| **Hosting VPS Hetzner CX32** | 4 vCPU, 8 GB RAM, 80 GB SSD |
| **Dominio y SSL** | Registro, renovación y certificado HTTPS |
| **Backups diarios** | PostgreSQL con retención de 30 días |
| **Corrección de bugs** | Sin costo adicional |
| **Actualizaciones de seguridad** | PHP, Laravel y dependencias |
| **Soporte técnico** | WhatsApp y correo Lun–Vie 9am–6pm |
| **Monitoreo del servidor** | Alertas de caída del servicio |

**Precio: S/. 300 – S/. 450/mes**

---

## ¿Qué NO incluye el mantenimiento?

| Concepto | Condición |
|---|---|
| Nuevos módulos o funcionalidades | Cotización por módulo |
| Cambios estructurales de lógica | Cotización según complejidad |
| Capacitaciones adicionales | S/. 100 – S/. 200 por sesión |
| App móvil | Cotización aparte (v2.0) |

---

## Cronograma de Cobro — Pago Único

| Hito | % |
|---|---|
| Firma del contrato e inicio | 40% |
| Entrega módulos core (auth, notas, asistencia, portal padre) | 30% |
| Entrega final + capacitación + go-live | 30% |

---

## Contrato Mínimo Recomendado: 6 meses

| Modalidad | Precio/mes | Ahorro vs. mensual |
|---|---|---|
| Mensual (mes a mes) | S/. 420 | — |
| Semestral anticipado | S/. 370 | S/. 300 |
| Anual anticipado | S/. 320 | S/. 1,200 |

---

## Proyección para el Equipo

Asumiendo S/. 6,500 desarrollo + S/. 380/mes mantenimiento:

| Concepto | Monto |
|---|---|
| Pago único (cobro) | S/. 6,500 |
| Costo real del VPS + dominio | ~S/. 45/mes |
| **Margen neto por mantenimiento** | ~S/. 335/mes |

Con 3 clientes activos:
- S/. 6,500 × 3 = **S/. 19,500** en pagos únicos
- S/. 380 × 3 = **S/. 1,140/mes** recurrentes netos

---

## Almacenamiento — Estimación de Costos

Una de las ventajas de guardar archivos en el VPS local (sin Cloudflare R2):

| Concepto | Estimación |
|---|---|
| Fotos de alumnos (300 alumnos, ~50 KB WebP c/u) | ~15 MB |
| Separatas PDF (100 archivos, ~500 KB c/u) | ~50 MB |
| Comprobantes PDF (1,000 archivos, ~200 KB c/u) | ~200 MB |
| **Total estimado para 1 año de operación** | **< 500 MB** |

Con 80 GB en el Hetzner CX32, el almacenamiento de archivos **no es un problema por varios años**. Cero costo adicional por storage externo.

---

## Visión a Futuro

CienciasNET puede replicarse para otros colegios del Perú. El primer cliente (Colegio Ciencias) valida el producto en producción real. A partir del segundo cliente el tiempo de desarrollo se reduce significativamente y el equipo puede evaluar:

- Repositorios independientes por colegio (enfoque actual)
- Multi-tenancy con una sola instalación y múltiples colegios (cuando el volumen de clientes lo justifique)
