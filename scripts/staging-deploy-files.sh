#!/usr/bin/env bash
set -euo pipefail

# Точечный деплой файлов на staging: pre-check → upload (tar|ssh) → queue restart → smoke.
#
# Единственный поддерживаемый способ выкладки файлов на staging.
# НЕ используйте голый scp/rsync — обойдёте защиту от затирания чужого кода.
#
# Использование:
#   STAGING_HOST=root@HOST bash scripts/staging-deploy-files.sh file1 [file2 ...]
#   bash scripts/staging-deploy-files.sh --force file1   # обойти pre-check (нежелательно)
#   bash scripts/staging-deploy-files.sh --skip-smoke file1
#   bash scripts/staging-deploy-files.sh --fix-permissions file1
#   bash scripts/staging-deploy-files.sh --no-queue-restart file1
#
# После upload по умолчанию перезапускается queue worker (supervisor casini-queue),
# иначе долгий queue:work держит старый PHP в памяти (пример: Unknown collector).
#
# Pre-check блокирует деплой, если локальный файл:
#   - теряет маркеры из scripts/staging-hot-zones.conf (есть на staging, нет локально);
#   - заметно короче версии на staging (GENERAL_SHRINK=1 по умолчанию).
#
# «Горячие» файлы (staging-hot-zones.conf): перед деплоем обязателен merge со staging —
# не выкладывать урезанную копию с feature-ветки (см. .cursor/rules/casini-project-workflow.mdc).
#
# Переменные: STAGING_HOST, STAGING_APP_DIR, SSH_OPTS (как в staging-pre-deploy-check.sh)
#             GENERAL_SHRINK, SHRINK_RATIO, HOT_ZONES_CONF
#             STAGING_QUEUE_SUPERVISOR (по умолчанию casini-queue)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

STAGING_HOST="${STAGING_HOST:-}"
STAGING_APP_DIR="${STAGING_APP_DIR:-/var/www/casini}"
SSH_OPTS="${SSH_OPTS:--o BatchMode=yes -o ConnectTimeout=15}"
STAGING_QUEUE_SUPERVISOR="${STAGING_QUEUE_SUPERVISOR:-casini-queue}"

FORCE=false
SKIP_SMOKE=false
FIX_PERMISSIONS=false
RESTART_QUEUE=true
ARGS=()

usage() {
  cat <<'EOF'
Usage: staging-deploy-files.sh [options] <file> [file ...]

Options:
  --force             передать --force в pre-deploy-check
  --skip-smoke        не запускать staging-smoke.sh после upload
  --fix-permissions  после upload: staging-fix-permissions.sh на сервере
  --no-queue-restart  не перезапускать queue worker после upload
  -h, --help          эта справка

Env:
  STAGING_HOST              обязателен (root@HOST)
  STAGING_APP_DIR           по умолчанию /var/www/casini
  SSH_OPTS                  опции ssh
  STAGING_QUEUE_SUPERVISOR  имя процесса supervisor (по умолчанию casini-queue)
  GENERAL_SHRINK            см. staging-pre-deploy-check.sh

Горячие файлы (staging-hot-zones.conf): деплой только после merge со staging.
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    -h|--help)
      usage
      exit 0
      ;;
    --force)
      FORCE=true
      shift
      ;;
    --skip-smoke)
      SKIP_SMOKE=true
      shift
      ;;
    --fix-permissions)
      FIX_PERMISSIONS=true
      shift
      ;;
    --no-queue-restart)
      RESTART_QUEUE=false
      shift
      ;;
    -*)
      echo "FAIL: неизвестный флаг: $1"
      usage
      exit 2
      ;;
    *)
      ARGS+=("$1")
      shift
      ;;
  esac
done

if [[ ${#ARGS[@]} -eq 0 ]]; then
  echo "FAIL: укажите хотя бы один файл"
  usage
  exit 2
fi

if [[ -z "${STAGING_HOST}" ]]; then
  echo "FAIL: задайте STAGING_HOST (например root@193.107.239.233)"
  exit 2
fi

cd "${REPO_ROOT}"

normalize_path() {
  local p="$1"
  p="${p#./}"
  printf '%s' "$p"
}

# Проверка существования локальных файлов
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  if [[ ! -f "${REPO_ROOT}/${rel}" ]]; then
    echo "FAIL: локальный файл не найден: ${rel}"
    exit 2
  fi
done

echo "==> 1/4 Pre-deploy check"
PRE_ARGS=()
if [[ "${FORCE}" == true ]]; then
  PRE_ARGS+=(--force)
fi
PRE_ARGS+=("${ARGS[@]}")
STAGING_HOST="${STAGING_HOST}" \
STAGING_APP_DIR="${STAGING_APP_DIR}" \
SSH_OPTS="${SSH_OPTS}" \
  bash "${SCRIPT_DIR}/staging-pre-deploy-check.sh" "${PRE_ARGS[@]}"

echo "==> 2/4 upload (tar|ssh) → ${STAGING_HOST}:${STAGING_APP_DIR}"
UPLOAD_LIST=()
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  UPLOAD_LIST+=("${rel}")
done

# Один архив по SSH — без голого scp; каталоги на remote создаёт tar.
# Важно: без ssh -n (иначе stdin=/dev/null и pipe с tar пустой).
# COPYFILE_DISABLE: без macOS xattr в архиве (шум на Linux tar).
SSH_UPLOAD_OPTS="${SSH_OPTS}"
SSH_UPLOAD_OPTS="${SSH_UPLOAD_OPTS//-n/}"
# shellcheck disable=SC2086
if ! (
  cd "${REPO_ROOT}"
  COPYFILE_DISABLE=1 tar -cf - "${UPLOAD_LIST[@]}"
) | ssh ${SSH_UPLOAD_OPTS} "${STAGING_HOST}" "cd '${STAGING_APP_DIR}' && tar -xf -"; then
  echo "FAIL: upload через tar|ssh не удался"
  exit 1
fi

for rel in "${UPLOAD_LIST[@]}"; do
  echo "OK: uploaded ${rel}"
done

if [[ "${FIX_PERMISSIONS}" == true ]]; then
  echo "==> Fix permissions on staging"
  # shellcheck disable=SC2086
  ssh -n ${SSH_OPTS} "${STAGING_HOST}" "cd '${STAGING_APP_DIR}' && bash scripts/staging-fix-permissions.sh"
fi

restart_staging_queue() {
  local supervisor_name="${STAGING_QUEUE_SUPERVISOR}"
  local ssh_restart_opts="${SSH_OPTS}"
  # Без -n: heredoc должен попасть на stdin remote bash.
  ssh_restart_opts="${ssh_restart_opts//-n/}"
  echo "==> 3/4 Restart queue worker (${supervisor_name})"
  # shellcheck disable=SC2086
  if ! ssh ${ssh_restart_opts} "${STAGING_HOST}" bash -s -- "${STAGING_APP_DIR}" "${supervisor_name}" <<'REMOTE'
set -euo pipefail
APP_DIR="$1"
SUPERVISOR_NAME="$2"
cd "${APP_DIR}"

# Сигнал воркерам Laravel завершиться после текущей задачи.
if sudo -u www-data env HOME=/tmp XDG_CONFIG_HOME=/tmp php artisan queue:restart >/dev/null 2>&1; then
  echo "OK: php artisan queue:restart"
else
  echo "WARN: php artisan queue:restart не удалось — продолжаем supervisor restart"
fi

if ! command -v supervisorctl >/dev/null 2>&1; then
  echo "FAIL: supervisorctl не найден — queue worker мог остаться со старым кодом"
  exit 1
fi

if supervisorctl status "${SUPERVISOR_NAME}" >/dev/null 2>&1; then
  supervisorctl restart "${SUPERVISOR_NAME}"
else
  echo "FAIL: supervisor process '${SUPERVISOR_NAME}' не найден"
  supervisorctl status || true
  exit 1
fi

# Короткая проверка, что процесс снова RUNNING
sleep 1
if supervisorctl status "${SUPERVISOR_NAME}" | grep -q RUNNING; then
  echo "OK: ${SUPERVISOR_NAME} RUNNING (новый PHP-код подхвачен)"
else
  echo "FAIL: ${SUPERVISOR_NAME} не в статусе RUNNING после restart"
  supervisorctl status "${SUPERVISOR_NAME}" || true
  exit 1
fi
REMOTE
  then
    echo "FAIL: не удалось перезапустить queue worker на staging"
    exit 1
  fi
}

if [[ "${RESTART_QUEUE}" == true ]]; then
  restart_staging_queue
else
  echo "==> 3/4 Queue restart skipped (--no-queue-restart)"
fi

if [[ "${SKIP_SMOKE}" == true ]]; then
  echo "==> 4/4 Smoke skipped (--skip-smoke)"
  echo "HINT: ssh ${STAGING_HOST} \"cd ${STAGING_APP_DIR} && bash scripts/staging-smoke.sh\""
  echo "OK: deploy finished (без smoke)"
  exit 0
fi

echo "==> 4/4 Smoke on staging"
# shellcheck disable=SC2086
if ssh -n ${SSH_OPTS} "${STAGING_HOST}" "cd '${STAGING_APP_DIR}' && bash scripts/staging-smoke.sh"; then
  echo "OK: deploy + smoke passed"
  exit 0
fi

echo "FAIL: smoke не прошёл — проверьте staging"
echo "HINT: bash scripts/staging-fix-permissions.sh --clear"
exit 1
