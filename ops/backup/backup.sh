#!/bin/sh
set -eu

timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_root="${BACKUP_ROOT:-/backups}"
workdir="${backup_root}/work/${timestamp}"
outdir="${backup_root}/daily"
archive="${outdir}/cienciasnet_${timestamp}.tar.gz"
encrypted="${archive}.enc"
manifest="${archive}.sha256"

alert() {
  message="$1"
  if [ -n "${BACKUP_ALERT_WEBHOOK_URL:-}" ]; then
    curl -fsS -X POST -H 'Content-Type: application/json' \
      --data "{\"text\":\"${message}\"}" \
      "${BACKUP_ALERT_WEBHOOK_URL}" >/dev/null || true
  fi
}

fail() {
  alert "CienciasNET backup failed: $1"
  echo "ERROR: $1" >&2
  exit 1
}

[ -n "${DB_HOST:-}" ] || fail "DB_HOST is required"
[ -n "${DB_DATABASE:-}" ] || fail "DB_DATABASE is required"
[ -n "${DB_USERNAME:-}" ] || fail "DB_USERNAME is required"
[ -n "${DB_PASSWORD:-}" ] || fail "DB_PASSWORD is required"
[ -n "${BACKUP_ENCRYPTION_PASSPHRASE_FILE:-}" ] || fail "BACKUP_ENCRYPTION_PASSPHRASE_FILE is required"
[ -r "${BACKUP_ENCRYPTION_PASSPHRASE_FILE}" ] || fail "backup passphrase file is not readable"

mkdir -p "${workdir}" "${outdir}"

export PGPASSWORD="${DB_PASSWORD}"
if ! pg_dump -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" --format=custom --file="${workdir}/postgres.dump"; then
  fail "pg_dump failed"
fi

if [ -d /var/lib/cienciasnet/private ]; then
  if ! tar -C /var/lib/cienciasnet -czf "${workdir}/private-files.tar.gz" private; then
    fail "private files archive failed"
  fi
else
  mkdir -p "${workdir}/private-empty"
  if ! tar -C "${workdir}" -czf "${workdir}/private-files.tar.gz" private-empty; then
    fail "private empty archive failed"
  fi
fi

{
  echo "timestamp=${timestamp}"
  echo "database=${DB_DATABASE}"
  echo "private_files_archive=private-files.tar.gz"
  echo "r2_bucket=${R2_BUCKET:-not_configured}"
} >"${workdir}/inventory.txt"

if ! tar -C "${workdir}" -czf "${archive}" postgres.dump private-files.tar.gz inventory.txt; then
  fail "backup bundle failed"
fi

sha256sum "${archive}" >"${manifest}" || fail "checksum failed"
if ! openssl enc -aes-256-cbc -salt -pbkdf2 -in "${archive}" -out "${encrypted}" -pass "file:${BACKUP_ENCRYPTION_PASSPHRASE_FILE}"; then
  fail "encryption failed"
fi
sha256sum "${encrypted}" >"${encrypted}.sha256" || fail "encrypted checksum failed"

rm -rf "${workdir}" "${archive}" "${manifest}"

find "${outdir}" -name 'cienciasnet_*.tar.gz.enc' -mtime +"${BACKUP_RETENTION_DAILY:-30}" -delete

alert "CienciasNET backup completed: ${encrypted}"
echo "${encrypted}"
