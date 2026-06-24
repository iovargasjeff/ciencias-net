# Production Operations

This folder contains executable operations support for `OPS-003`.

## Secrets

Keep production secrets outside the repository. Copy `.env.production.example` to a protected host path such as
`/etc/cienciasnet/production.env` and set `CIENCIASNET_ENV_FILE` when running compose.

The backup passphrase file referenced by `BACKUP_ENCRYPTION_PASSPHRASE_FILE` must be readable only by the deployment
user.

## Deploy

```bash
export CIENCIASNET_ENV_FILE=/etc/cienciasnet/production.env
docker compose -f docker-compose.production.yml up -d --build
docker compose -f docker-compose.production.yml ps
```

Only the frontend container binds a local reverse-proxy port. PostgreSQL, PHP-FPM, API Nginx, queue, scheduler and
facial service stay on Docker networks.

## Backup

```bash
export CIENCIASNET_ENV_FILE=/etc/cienciasnet/production.env
docker compose -f docker-compose.production.yml --profile operations run --rm backup
```

The backup includes PostgreSQL, private backend files, an inventory, SHA-256 checksums and AES-256-CBC encryption.
Replicate the resulting `.enc` and `.sha256` files outside the VPS.

## Restore Drill

Run restoration quarterly in an isolated environment:

```bash
docker run --rm \
  --env-file /etc/cienciasnet/production.env \
  -v /backups/cienciasnet:/backups:ro \
  -v ./ops/backup:/ops/backup:ro \
  -v cienciasnet_restore_private:/var/lib/cienciasnet \
  postgres:16-alpine \
  /bin/sh /ops/backup/restore.sh /backups/daily/cienciasnet_YYYYmmddTHHMMSSZ.tar.gz.enc
```

After restore, verify login, health, representative account statement, private file download and facial service
connectivity before declaring the drill successful.
