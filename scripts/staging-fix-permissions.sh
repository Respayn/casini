#!/usr/bin/env bash
set -euo pipefail

# Восстанавливает права storage/ и bootstrap/cache/ для PHP-FPM (www-data).
# Нужен после php artisan / composer от root на staging —
# иначе Livewire Compiler падает с tempnam() ErrorException.
#
# Использование:
#   bash scripts/staging-fix-permissions.sh
#   bash scripts/staging-fix-permissions.sh --clear

APP_DIR="${APP_DIR:-/var/www/casini}"
WEB_USER="${WEB_USER:-www-data}"
WEB_GROUP="${WEB_GROUP:-www-data}"
CLEAR_CACHE=false

for arg in "$@"; do
  case "$arg" in
    --clear) CLEAR_CACHE=true ;;
    -h|--help)
      echo "Usage: $0 [--clear]"
      echo "  --clear  also run: sudo -u ${WEB_USER} php artisan optimize:clear"
      exit 0
      ;;
  esac
done

cd "${APP_DIR}"

echo "==> Fix misplaced bootstrap cache files (if any)"
mv -f storage/framework/views/packages.php bootstrap/cache/ 2>/dev/null || true
mv -f storage/framework/views/services.php bootstrap/cache/ 2>/dev/null || true

echo "==> chown ${WEB_USER}:${WEB_GROUP} storage bootstrap/cache"
chown -R "${WEB_USER}:${WEB_GROUP}" storage bootstrap/cache

echo "==> chmod directories 775, files 664"
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

if [[ "${CLEAR_CACHE}" == "true" ]]; then
  echo "==> optimize:clear as ${WEB_USER}"
  sudo -u "${WEB_USER}" php artisan optimize:clear
fi

echo "==> Verify Livewire compiler cache writable"
LIVEWIRE_CLASSES="${APP_DIR}/storage/framework/views/livewire/classes"
mkdir -p "${LIVEWIRE_CLASSES}"
chown "${WEB_USER}:${WEB_GROUP}" "${APP_DIR}/storage/framework/views/livewire" "${LIVEWIRE_CLASSES}" 2>/dev/null || true
chmod 775 "${APP_DIR}/storage/framework/views/livewire" "${LIVEWIRE_CLASSES}" 2>/dev/null || true

if sudo -u "${WEB_USER}" test -w "${LIVEWIRE_CLASSES}"; then
  echo "OK: ${LIVEWIRE_CLASSES} writable by ${WEB_USER}"
else
  echo "FAIL: ${LIVEWIRE_CLASSES} not writable by ${WEB_USER}"
  exit 1
fi

echo "==> Permissions fixed"
