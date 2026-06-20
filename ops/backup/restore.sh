#!/bin/sh
set -eu

backup_file="${1:-}"
restore_root="${RESTORE_ROOT:-/restore/cienciasnet}"

fail() {
  echo "ERROR: $1" >&2
  exit 1
}

[ -n "${backup_file}" ] || fail "Usage: restore.sh /path/to/cienciasnet_YYYYmmddTHHMMSSZ.tar.gz.enc"
[ -r "${backup_file}" ] || fail "backup file is not readable"
[ -n "${BACKUP_ENCRYPTION_PASSPHRASE_FILE:-}" ] || fail "BACKUP_ENCRYPTION_PASSPHRASE_FILE is required"
[ -r "${BACKUP_ENCRYPTION_PASSPHRASE_FILE}" ] || fail "backup passphrase file is not readable"
[ -n "${DB_HOST:-}" ] || fail "DB_HOST is required"
[ -n "${DB_DATABASE:-}" ] || fail "DB_DATABASE is required"
[ -n "${DB_USERNAME:-}" ] || fail "DB_USERNAME is required"
[ -n "${DB_PASSWORD:-}" ] || fail "DB_PASSWORD is required"

mkdir -p "${restore_root}"
decrypted="${restore_root}/bundle.tar.gz"

if [ -r "${backup_file}.sha256" ]; then
  sha256sum -c "${backup_file}.sha256" || fail "encrypted checksum mismatch"
fi

openssl enc -d -aes-256-cbc -pbkdf2 -in "${backup_file}" -out "${decrypted}" -pass "file:${BACKUP_ENCRYPTION_PASSPHRASE_FILE}" \
  || fail "decryption failed"

tar -C "${restore_root}" -xzf "${decrypted}" || fail "bundle extraction failed"

export PGPASSWORD="${DB_PASSWORD}"
pg_restore -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" --clean --if-exists "${restore_root}/postgres.dump" \
  || fail "database restore failed"

mkdir -p /var/lib/cienciasnet
tar -C /var/lib/cienciasnet -xzf "${restore_root}/private-files.tar.gz" || fail "private files restore failed"

echo "Restore completed. Run application smoke tests before promoting this environment."
