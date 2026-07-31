#!/usr/bin/env bash
set -euo pipefail

# Smoke-check staging после деплоя: страница логина, admin, права Livewire-кэша.
# Не запускает php artisan test и не трогает БД.

APP_DIR="${APP_DIR:-/var/www/casini}"
LOGIN_URL="${LOGIN_URL:-https://test.casini.ru/login}"
BASE_URL="${BASE_URL:-https://test.casini.ru}"
WEB_USER="${WEB_USER:-www-data}"

if [[ "${LOGIN_URL}" == http://127.0.0.1/* ]] || [[ "${LOGIN_URL}" == http://localhost/* ]]; then
  echo "WARN: локальный URL может отдавать 404 за Nginx; по умолчанию используйте https://test.casini.ru/login"
fi

echo "==> HTTP login page: ${LOGIN_URL}"
html="$(curl -fsS --max-time 20 "${LOGIN_URL}")"

if ! printf '%s' "${html}" | grep -q 'wire:submit.prevent="login"'; then
  echo "FAIL: на странице входа нет wire:submit.prevent=\"login\""
  exit 1
fi
echo "OK: login form Livewire binding present"

echo "==> DB admin user exists"
cd "${APP_DIR}"
php artisan tinker --execute="echo App\Models\User::where('login','admin')->exists() ? 'OK' : 'FAIL';" | tee /tmp/casini-smoke-admin.txt

if ! grep -q 'OK' /tmp/casini-smoke-admin.txt; then
  echo "FAIL: пользователь admin отсутствует в БД"
  exit 1
fi

echo "==> Livewire compiler cache writable by ${WEB_USER}"
LIVEWIRE_CLASSES="${APP_DIR}/storage/framework/views/livewire/classes"
mkdir -p "${LIVEWIRE_CLASSES}"

if ! sudo -u "${WEB_USER}" test -w "${LIVEWIRE_CLASSES}"; then
  echo "FAIL: ${LIVEWIRE_CLASSES} не доступен для записи ${WEB_USER}"
  echo "HINT: запустите bash scripts/staging-fix-permissions.sh --clear"
  exit 1
fi
echo "OK: Livewire cache writable"

echo "==> public/storage symlink (user photos, logos)"
if [[ ! -L "${APP_DIR}/public/storage" ]]; then
  echo "FAIL: отсутствует симлинк ${APP_DIR}/public/storage"
  echo "HINT: cd ${APP_DIR} && php artisan storage:link && chown -h ${WEB_USER}:${WEB_USER} public/storage"
  exit 1
fi
echo "OK: public/storage -> $(readlink "${APP_DIR}/public/storage")"

echo "==> Dictionaries without auth (expect 302 to login, not 500)"
dict_headers="$(curl -sI --max-time 20 "${BASE_URL}/system-settings/dictionaries")"
dict_status="$(printf '%s' "${dict_headers}" | awk 'NR==1 {print $2}')"
dict_location="$(printf '%s' "${dict_headers}" | awk 'tolower($1)=="location:" {print $2}' | tr -d '\r')"

if [[ "${dict_status}" == "500" ]]; then
  echo "FAIL: /system-settings/dictionaries вернул 500 (часто права storage/Livewire)"
  echo "HINT: bash scripts/staging-fix-permissions.sh --clear"
  exit 1
fi

if [[ "${dict_status}" != "302" && "${dict_status}" != "301" ]]; then
  echo "FAIL: ожидался редирект на login, получен HTTP ${dict_status}"
  exit 1
fi

if ! printf '%s' "${dict_location}" | grep -q 'login'; then
  echo "FAIL: Location не ведёт на login: ${dict_location}"
  exit 1
fi
echo "OK: dictionaries redirects to login (${dict_status})"

echo "==> Reference integrations exist"
integrations_count="$(php artisan tinker --execute="echo App\Models\Integration::count();" | tr -d '[:space:]')"
if [[ "${integrations_count}" -lt 1 ]]; then
  echo "FAIL: integrations=${integrations_count} (пустые модалки «Добавить интеграцию»)"
  echo "HINT: bash scripts/staging-reseed-reference.sh"
  exit 1
fi
echo "OK: integrations=${integrations_count}"

echo "==> Yandex Direct OAuth env (warning only)"
direct_client_id="$(php artisan tinker --execute="echo config('services.yandex_direct.client_id') ?: '';" | tr -d '[:space:]')"
if [[ -z "${direct_client_id}" ]]; then
  echo "WARN: YANDEX_DIRECT_CLIENT_ID пуст — OAuth Яндекс.Директ вернёт 400 client_id"
else
  echo "OK: YANDEX_DIRECT_CLIENT_ID задан"
fi

echo "==> Route users.edit exists"
route_list="$(php artisan route:list --path='users/{user}/edit' --columns=method,uri,name,middleware 2>/dev/null || true)"
if ! printf '%s' "${route_list}" | grep -qE 'users\.edit|users/\{user\}/edit'; then
  echo "FAIL: маршрут users/{user}/edit (users.edit) не найден"
  printf '%s\n' "${route_list}"
  exit 1
fi
echo "OK: route users.edit present"

echo "==> Middleware alias can.access.user.edit (если используется в routes)"
if grep -qE "can\.access\.user\.edit" "${APP_DIR}/routes/web.php" 2>/dev/null; then
  if ! grep -qE "['\"]can\.access\.user\.edit['\"]" "${APP_DIR}/bootstrap/app.php"; then
    echo "FAIL: routes используют can.access.user.edit, но alias не объявлен в bootstrap/app.php"
    echo "HINT: не выкатывайте bootstrap/app.php без merge с feature/roles-permissions; см. scripts/staging-pre-deploy-check.sh"
    exit 1
  fi
  # Доп. проверка: класс middleware резолвится через контейнер
  mw_resolve="$(php artisan tinker --execute="echo class_exists(\\App\\Http\\Middleware\\EnsureCanAccessUserEdit::class) ? 'OK' : 'FAIL';" 2>/dev/null | tr -d '[:space:]' || true)"
  if [[ "${mw_resolve}" == "FAIL" ]]; then
    echo "FAIL: класс EnsureCanAccessUserEdit не найден (alias есть, файла middleware нет)"
    exit 1
  fi
  if [[ "${mw_resolve}" == "OK" ]]; then
    echo "OK: alias can.access.user.edit + class EnsureCanAccessUserEdit"
  else
    echo "OK: alias can.access.user.edit present in bootstrap (class check skipped)"
  fi
else
  echo "OK: can.access.user.edit не используется в routes — skip"
fi

echo "==> Smoke passed"
