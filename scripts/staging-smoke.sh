#!/usr/bin/env bash
set -euo pipefail

# Smoke-check staging после деплоя: страница логина, admin, права Livewire-кэша.
# Не запускает php artisan test и не трогает БД.

APP_DIR="${APP_DIR:-/var/www/casini}"
LOGIN_URL="${LOGIN_URL:-https://test.casini.ru/login}"
BASE_URL="${BASE_URL:-https://test.casini.ru}"
WEB_USER="${WEB_USER:-www-data}"
SMOKE_ADMIN_LOGIN="${SMOKE_ADMIN_LOGIN:-admin}"
SMOKE_ADMIN_PASSWORD="${SMOKE_ADMIN_PASSWORD:-admin1234}"

if [[ "${LOGIN_URL}" == http://127.0.0.1/* ]] || [[ "${LOGIN_URL}" == http://localhost/* ]]; then
  echo "WARN: локальный URL может отдавать 404 за Nginx; по умолчанию используйте https://test.casini.ru/login"
fi

echo "==> HTTP login page: ${LOGIN_URL}"
# В файл, не в bash-переменную: Debugbar/Livewire могут отдать NUL-байты,
# из‑за них $(curl) обрезает HTML и smoke ложно падает на проверке формы.
login_html="$(mktemp)"
trap 'rm -f "${login_html}"' EXIT
curl -fsS --max-time 20 "${LOGIN_URL}" -o "${login_html}"

# Старая форма: wire:submit.prevent="login"; актуальная на staging — Alpine + $wire.login() + captcha.
if grep -q 'wire:submit.prevent="login"' "${login_html}"; then
  echo "OK: login form Livewire binding present"
elif grep -Fq '$wire.login()' "${login_html}" && grep -q 'pages::auth.login' "${login_html}"; then
  echo "OK: login form Livewire + captcha submit present"
else
  echo "FAIL: на странице входа нет ни wire:submit.prevent=\"login\", ни \$wire.login()"
  exit 1
fi

echo "==> DB admin user + password"
cd "${APP_DIR}"
php artisan tinker --execute="echo App\Models\User::where('login','admin')->exists() ? 'OK' : 'FAIL';" | tee /tmp/casini-smoke-admin.txt

if ! grep -q 'OK' /tmp/casini-smoke-admin.txt; then
  echo "FAIL: пользователь ${SMOKE_ADMIN_LOGIN} отсутствует в БД"
  exit 1
fi
echo "OK: user ${SMOKE_ADMIN_LOGIN} exists"

# Проверка хеша пароля (как на странице входа: login + Hash::check).
php artisan tinker --execute="\$u=App\Models\User::where('login','${SMOKE_ADMIN_LOGIN}')->first(); echo (\$u && Illuminate\Support\Facades\Hash::check('${SMOKE_ADMIN_PASSWORD}', \$u->password)) ? 'OK' : 'FAIL';" | tee /tmp/casini-smoke-auth.txt

if ! grep -q 'OK' /tmp/casini-smoke-auth.txt; then
  echo "FAIL: пароль smoke для ${SMOKE_ADMIN_LOGIN} не подходит (ожидается SMOKE_ADMIN_PASSWORD)"
  exit 1
fi
echo "OK: ${SMOKE_ADMIN_LOGIN} password accepted"

echo "==> Livewire compiler cache writable by ${WEB_USER}"
LIVEWIRE_CLASSES="${APP_DIR}/storage/framework/views/livewire/classes"
mkdir -p "${LIVEWIRE_CLASSES}"

if ! sudo -u "${WEB_USER}" test -w "${LIVEWIRE_CLASSES}"; then
  echo "FAIL: ${LIVEWIRE_CLASSES} не доступен для записи ${WEB_USER}"
  echo "HINT: запустите bash scripts/staging-fix-permissions.sh --clear"
  exit 1
fi
echo "OK: Livewire cache writable"

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

echo "==> Smoke passed"
