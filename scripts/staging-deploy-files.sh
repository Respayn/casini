#!/usr/bin/env bash
set -euo pipefail

# Точечный деплой файлов на staging: pre-check → scp → smoke.
#
# Использование:
#   STAGING_HOST=root@HOST bash scripts/staging-deploy-files.sh file1 [file2 ...]
#   bash scripts/staging-deploy-files.sh --force file1   # обойти pre-check (нежелательно)
#   bash scripts/staging-deploy-files.sh --skip-smoke file1
#   bash scripts/staging-deploy-files.sh --fix-permissions file1
#
# Переменные: STAGING_HOST, STAGING_APP_DIR, SSH_OPTS (как в staging-pre-deploy-check.sh)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

STAGING_HOST="${STAGING_HOST:-}"
STAGING_APP_DIR="${STAGING_APP_DIR:-/var/www/casini}"
SSH_OPTS="${SSH_OPTS:--o BatchMode=yes -o ConnectTimeout=15}"

FORCE=false
SKIP_SMOKE=false
FIX_PERMISSIONS=false
ARGS=()

usage() {
  cat <<'EOF'
Usage: staging-deploy-files.sh [options] <file> [file ...]

Options:
  --force             передать --force в pre-deploy-check
  --skip-smoke        не запускать staging-smoke.sh после scp
  --fix-permissions  после scp: staging-fix-permissions.sh на сервере
  -h, --help          эта справка

Env:
  STAGING_HOST        обязателен (root@HOST)
  STAGING_APP_DIR     по умолчанию /var/www/casini
  SSH_OPTS            опции ssh/scp
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

echo "==> 1/3 Pre-deploy check"
PRE_ARGS=()
if [[ "${FORCE}" == true ]]; then
  PRE_ARGS+=(--force)
fi
PRE_ARGS+=("${ARGS[@]}")
STAGING_HOST="${STAGING_HOST}" \
STAGING_APP_DIR="${STAGING_APP_DIR}" \
SSH_OPTS="${SSH_OPTS}" \
  bash "${SCRIPT_DIR}/staging-pre-deploy-check.sh" "${PRE_ARGS[@]}"

echo "==> 2/3 scp → ${STAGING_HOST}:${STAGING_APP_DIR}"
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  remote_dir="$(dirname "${STAGING_APP_DIR}/${rel}")"
  # shellcheck disable=SC2086
  ssh -n ${SSH_OPTS} "${STAGING_HOST}" "mkdir -p '${remote_dir}'"
  # shellcheck disable=SC2086
  scp ${SSH_OPTS} "${REPO_ROOT}/${rel}" "${STAGING_HOST}:${STAGING_APP_DIR}/${rel}"
  echo "OK: uploaded ${rel}"
done

if [[ "${FIX_PERMISSIONS}" == true ]]; then
  echo "==> Fix permissions on staging"
  # shellcheck disable=SC2086
  ssh -n ${SSH_OPTS} "${STAGING_HOST}" "cd '${STAGING_APP_DIR}' && bash scripts/staging-fix-permissions.sh"
fi

if [[ "${SKIP_SMOKE}" == true ]]; then
  echo "==> Smoke skipped (--skip-smoke)"
  echo "HINT: ssh ${STAGING_HOST} \"cd ${STAGING_APP_DIR} && bash scripts/staging-smoke.sh\""
  echo "OK: deploy finished (без smoke)"
  exit 0
fi

echo "==> 3/3 Smoke on staging"
# shellcheck disable=SC2086
if ssh -n ${SSH_OPTS} "${STAGING_HOST}" "cd '${STAGING_APP_DIR}' && bash scripts/staging-smoke.sh"; then
  echo "OK: deploy + smoke passed"
  exit 0
fi

echo "FAIL: smoke не прошёл — проверьте staging"
echo "HINT: bash scripts/staging-fix-permissions.sh --clear"
exit 1
