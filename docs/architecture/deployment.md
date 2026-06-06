# Despliegue y Operación

Los comandos, configuraciones Nginx, opciones manual/Docker, variables y tareas operativas completas están en
[`deployment-runbook.md`](deployment-runbook.md).

## Componentes

- Nginx como terminación HTTPS y servidor del build frontend.
- Laravel/PHP-FPM, worker de colas y scheduler.
- PostgreSQL no expuesto a Internet.
- Servicio facial Python en red privada.
- Cloudflare R2 privado para biometría.
- Almacenamiento privado del backend sin `storage:link`.

Docker Compose es el entorno principal y reproducible para desarrollo, integración y despliegue inicial. La instalación
manual queda como alternativa operativa, no como flujo recomendado del equipo.

## Principios

- Solo puertos necesarios expuestos.
- HTTPS, HSTS y headers de seguridad.
- Secretos fuera del repositorio.
- Logs estructurados sin tokens, contraseñas, embeddings ni notas psicológicas.
- Health checks y alertas para API, colas, base de datos y servicio facial.

## Backups

- Alcance: PostgreSQL, archivos privados, objetos/inventario R2 y claves custodiadas.
- Backups cifrados y replicados fuera del VPS.
- Retención inicial: 30 diarios y 12 mensuales.
- Objetivos iniciales: RPO 24 horas y RTO 4 horas.
- Restauración completa probada trimestralmente.
