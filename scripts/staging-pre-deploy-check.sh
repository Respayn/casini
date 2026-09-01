#!/usr/bin/env bash
set -euo pipefail

# Pre-deploy проверка перед scp/rsync на staging.
# Блокирует деплой, если локальные файлы «срезают» merged-код с сервера.
#
# «Горячие» файлы (staging-hot-zones.conf): сначала merge со staging, потом деплой.
#
# Использование:
#   STAGING_HOST=root@HOST bash scripts/staging-pre-deploy-check.sh file1 [file2 ...]
#   bash scripts/staging-pre-deploy-check.sh --force file1   # осознанный обход (WARN)
#
# Exit codes: 0 = OK, 1 = блок, 2 = ошибка SSH/конфига
# Совместимо с bash 3.2 (macOS) и bash 4+.

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
HOT_ZONES_CONF="${HOT_ZONES_CONF:-${SCRIPT_DIR}/staging-hot-zones.conf}"

STAGING_HOST="${STAGING_HOST:-}"
STAGING_APP_DIR="${STAGING_APP_DIR:-/var/www/casini}"
SSH_OPTS="${SSH_OPTS:--o BatchMode=yes -o ConnectTimeout=15}"
SHRINK_RATIO="${SHRINK_RATIO:-0.85}"
DIFF_LINES="${DIFF_LINES:-40}"
PHP_BIN="${PHP_BIN:-}"

FORCE=false
FAILURES=0
WARNINGS=0

# php для php -l (опционально — на машинах без PHP локально не блокируем)
if [[ -z "${PHP_BIN}" ]]; then
  if command -v php >/dev/null 2>&1; then
    PHP_BIN="$(command -v php)"
  elif [[ -x /opt/homebrew/bin/php ]]; then
    PHP_BIN=/opt/homebrew/bin/php
  elif [[ -x /usr/local/bin/php ]]; then
    PHP_BIN=/usr/local/bin/php
  fi
fi

usage() {
  cat <<'EOF'
Usage: staging-pre-deploy-check.sh [--force] <file> [file ...]

  Сравнивает локальные файлы с staging и блокирует деплой при регрессии.

  «Горячие» файлы (scripts/staging-hot-zones.conf): перед деплоем — merge со staging,
  затем свои правки. Не выкладывать feature-ветку напрямую.

Переменные окружения:
  STAGING_HOST      обязателен (например root@193.107.239.233)
  STAGING_APP_DIR   путь на сервере (по умолчанию /var/www/casini)
  SSH_OPTS          опции ssh (по умолчанию BatchMode + ConnectTimeout)
  HOT_ZONES_CONF    путь к реестру горячих зон
  SHRINK_RATIO      порог укорочения (по умолчанию 0.85)
  GENERAL_SHRINK    1 (по умолчанию) — проверять укорочение для ВСЕХ файлов деплоя;
                    0 — только записи с __HOT_SHRINK__ в hot-zones.conf
  --force           не блокировать (exit 0), только WARN

Exit: 0 OK, 1 блок, 2 ошибка окружения/SSH
EOF
}

# Сбор аргументов (bash 3.2: без FILES+= в цикле с set --)
ARGS=()
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
  echo "FAIL: укажите хотя бы один файл для проверки"
  usage
  exit 2
fi

if [[ -z "${STAGING_HOST}" ]]; then
  echo "FAIL: задайте STAGING_HOST (например root@193.107.239.233)"
  exit 2
fi

if [[ ! -f "${HOT_ZONES_CONF}" ]]; then
  echo "FAIL: не найден реестр горячих зон: ${HOT_ZONES_CONF}"
  exit 2
fi

cd "${REPO_ROOT}"

fail() {
  echo "FAIL: $*"
  FAILURES=$((FAILURES + 1))
}

warn() {
  echo "WARN: $*"
  WARNINGS=$((WARNINGS + 1))
}

normalize_path() {
  local p="$1"
  p="${p#./}"
  printf '%s' "$p"
}

is_in_deploy_list() {
  local target="$1"
  local f
  for f in "${ARGS[@]}"; do
    if [[ "$(normalize_path "$f")" == "$target" ]]; then
      return 0
    fi
  done
  return 1
}

fetch_remote() {
  local rel="$1"
  local dest="$2"
  # -n: не читать stdin (иначе ssh съедает HOT_ZONES_CONF в while-read)
  # shellcheck disable=SC2086
  if ! ssh -n ${SSH_OPTS} "${STAGING_HOST}" "test -f '${STAGING_APP_DIR}/${rel}'" 2>/dev/null; then
    return 1
  fi
  # shellcheck disable=SC2086
  ssh -n ${SSH_OPTS} "${STAGING_HOST}" "cat '${STAGING_APP_DIR}/${rel}'" >"${dest}"
}

remote_cache_path() {
  local rel="$1"
  printf '%s/remote-%s' "${TMP_DIR}" "$(printf '%s' "${rel}" | tr '/' '_')"
}

TMP_DIR="$(mktemp -d "${TMPDIR:-/tmp}/casini-pre-deploy.XXXXXX")"
cleanup() {
  rm -rf "${TMP_DIR}"
}
trap cleanup EXIT

echo "==> Pre-deploy check → ${STAGING_HOST}:${STAGING_APP_DIR}"
echo "    files: ${ARGS[*]}"
if [[ "${FORCE}" == true ]]; then
  echo "    mode: --force (нарушения не блокируют)"
fi

# --- Local files exist ---
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  if [[ ! -f "${REPO_ROOT}/${rel}" ]]; then
    fail "локальный файл не найден: ${rel}"
  fi
done
if [[ "${FAILURES}" -gt 0 && "${FORCE}" != true ]]; then
  echo "BLOCKED: локальные файлы отсутствуют"
  exit 1
fi

# --- PHP syntax ---
echo "==> PHP syntax (php -l)"
if [[ -z "${PHP_BIN}" ]]; then
  warn "php не найден в PATH — skip php -l (задайте PHP_BIN при необходимости)"
else
  for rel_raw in "${ARGS[@]}"; do
    rel="$(normalize_path "$rel_raw")"
    local_path="${REPO_ROOT}/${rel}"
    [[ -f "${local_path}" ]] || continue
    case "${rel}" in
      *.php)
        if ! "${PHP_BIN}" -l "${local_path}" >/dev/null; then
          fail "синтаксис PHP: ${rel}"
        else
          echo "OK: php -l ${rel}"
        fi
        ;;
    esac
  done
fi

# --- Regression + shrink guards ---
echo "==> Hot-zone regression / shrink guards"
while IFS= read -r line || [[ -n "${line}" ]]; do
  case "${line}" in
    ''|\#*) continue ;;
  esac

  path="${line%%|*}"
  rest="${line#*|}"
  pattern="${rest%%|*}"
  reason="${rest#*|}"

  if ! is_in_deploy_list "${path}"; then
    continue
  fi

  local_path="${REPO_ROOT}/${path}"
  if [[ ! -f "${local_path}" ]]; then
    fail "локальный файл не найден: ${path}"
    continue
  fi

  remote_path="$(remote_cache_path "${path}")"
  if [[ ! -f "${remote_path}" ]]; then
    if ! fetch_remote "${path}" "${remote_path}"; then
      echo "OK: ${path} отсутствует на staging (новый файл) — skip hot-zone"
      continue
    fi
  fi

  if [[ "${pattern}" == "__HOT_SHRINK__" ]]; then
    local_lines="$(wc -l <"${local_path}" | tr -d '[:space:]')"
    remote_lines="$(wc -l <"${remote_path}" | tr -d '[:space:]')"
    if [[ "${remote_lines}" -gt 0 ]]; then
      too_small="$(awk -v l="${local_lines}" -v r="${remote_lines}" -v ratio="${SHRINK_RATIO}" \
        'BEGIN { print (l < r * ratio) ? 1 : 0 }')"
      if [[ "${too_small}" == "1" ]]; then
        fail "${path}: локальный файл заметно короче staging (${local_lines} строк < ${remote_lines}*${SHRINK_RATIO}) — вероятно затрёте чужой код (${reason})"
        echo "---- diff (first ${DIFF_LINES} lines) ${path} ----"
        diff -u "${remote_path}" "${local_path}" | head -n "${DIFF_LINES}" || true
        echo "----"
      else
        echo "OK: shrink ${path} (${local_lines}/${remote_lines} строк)"
      fi
    fi
    continue
  fi

  display_pattern="$(printf '%s' "${pattern}" | tr -d '\\')"
  if grep -qE -- "${pattern}" "${remote_path}"; then
    if ! grep -qE -- "${pattern}" "${local_path}"; then
      fail "${path}: на staging есть '${display_pattern}', в локальном файле нет — вы срежете: ${reason}"
      echo "---- diff (first ${DIFF_LINES} lines) ${path} ----"
      diff -u "${remote_path}" "${local_path}" | head -n "${DIFF_LINES}" || true
      echo "----"
    else
      echo "OK: marker '${display_pattern}' retained in ${path}"
    fi
  fi
done <"${HOT_ZONES_CONF}"

# --- General shrink guard for every file in the deploy list ---
# Ловит случаи, когда файл ещё не внесён в hot-zones.conf, но локальная копия
# заметно короче staging (типичный «затрём чужую feature-ветку»).
GENERAL_SHRINK="${GENERAL_SHRINK:-1}"
if [[ "${GENERAL_SHRINK}" == "1" ]]; then
  echo "==> General shrink guard (все файлы деплоя, порог ${SHRINK_RATIO})"
  for rel_raw in "${ARGS[@]}"; do
    rel="$(normalize_path "$rel_raw")"
    local_path="${REPO_ROOT}/${rel}"
    [[ -f "${local_path}" ]] || continue

    remote_path="$(remote_cache_path "${rel}")"
    if [[ ! -f "${remote_path}" ]]; then
      if ! fetch_remote "${rel}" "${remote_path}"; then
        continue
      fi
    fi

    local_lines="$(wc -l <"${local_path}" | tr -d '[:space:]')"
    remote_lines="$(wc -l <"${remote_path}" | tr -d '[:space:]')"
    # Мелкие файлы и конфиги не гоняем — шум; порог по умолчанию 40 строк на remote
    if [[ "${remote_lines}" -lt 40 ]]; then
      continue
    fi

    too_small="$(awk -v l="${local_lines}" -v r="${remote_lines}" -v ratio="${SHRINK_RATIO}" \
      'BEGIN { print (l < r * ratio) ? 1 : 0 }')"
    if [[ "${too_small}" == "1" ]]; then
      fail "${rel}: локальный файл заметно короче staging (${local_lines} < ${remote_lines}*${SHRINK_RATIO}) — вероятна потеря чужого кода. Merge/cherry-pick нужную ветку или GENERAL_SHRINK=0 / --force"
      echo "---- diff (first ${DIFF_LINES} lines) ${rel} ----"
      diff -u "${remote_path}" "${local_path}" | head -n "${DIFF_LINES}" || true
      echo "----"
    fi
  done
else
  echo "==> General shrink guard skipped (GENERAL_SHRINK=0)"
fi

# Краткая сводка отличий для файлов без FAIL
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  local_path="${REPO_ROOT}/${rel}"
  [[ -f "${local_path}" ]] || continue
  remote_path="$(remote_cache_path "${rel}")"
  if [[ ! -f "${remote_path}" ]]; then
    fetch_remote "${rel}" "${remote_path}" 2>/dev/null || continue
  fi
  if ! cmp -s "${remote_path}" "${local_path}"; then
    echo "INFO: ${rel} отличается от staging (это нормально, если правки ваши)"
  fi
done

# --- Middleware consistency ---
echo "==> Middleware consistency (routes vs effective bootstrap)"

ROUTES_REMOTE="${TMP_DIR}/routes-web-remote.php"
BOOTSTRAP_EFFECTIVE="${TMP_DIR}/bootstrap-effective.php"

if fetch_remote "routes/web.php" "${ROUTES_REMOTE}"; then
  if is_in_deploy_list "routes/web.php"; then
    ROUTES_EFFECTIVE="${REPO_ROOT}/routes/web.php"
  else
    ROUTES_EFFECTIVE="${ROUTES_REMOTE}"
  fi

  BOOTSTRAP_OK=false
  if is_in_deploy_list "bootstrap/app.php"; then
    cp "${REPO_ROOT}/bootstrap/app.php" "${BOOTSTRAP_EFFECTIVE}"
    BOOTSTRAP_OK=true
  else
    if fetch_remote "bootstrap/app.php" "${BOOTSTRAP_EFFECTIVE}"; then
      BOOTSTRAP_OK=true
    else
      fail "не удалось получить bootstrap/app.php со staging"
    fi
  fi

  if [[ "${BOOTSTRAP_OK}" == true ]]; then
    # Достаём аргументы из middleware([...]) / middleware('...')
    MW_LIST="${TMP_DIR}/mw-list.txt"
    : >"${MW_LIST}"
    # shellcheck disable=SC2016
    grep -oE "middleware\(\[[^]]*\]\)|middleware\('[^']+'\)|middleware\(\"[^\"]+\"\)" \
      "${ROUTES_EFFECTIVE}" 2>/dev/null \
      | sed -E "s/^middleware\(\[//; s/\]\)$//; s/^middleware\(//; s/\)$//; s/['\"]//g; s/,/ /g" \
      | tr ' ' '\n' \
      | sed '/^$/d' \
      | sort -u >"${MW_LIST}" || true

    found_any=false
    while IFS= read -r mw || [[ -n "${mw}" ]]; do
      [[ -z "${mw}" ]] && continue
      # Spatie и стандартные — пропускаем
      case "${mw}" in
        auth|guest|throttle|verified|signed|web|api|can) continue ;;
        permission:*|role:*|role_or_permission:*) continue ;;
      esac
      # Кастомные aliases обычно с точкой (can.access.user.edit)
      case "${mw}" in
        *.*)
          found_any=true
          escaped="$(printf '%s' "${mw}" | sed 's/\./\\./g')"
          if ! grep -qE "['\"]${escaped}['\"]" "${BOOTSTRAP_EFFECTIVE}"; then
            fail "middleware '${mw}' есть в routes/web.php, но нет в effective bootstrap/app.php (alias)"
          else
            echo "OK: alias '${mw}' present in effective bootstrap"
          fi
          ;;
      esac
    done <"${MW_LIST}"

    if [[ "${found_any}" == false ]]; then
      echo "OK: кастомных dotted middleware в routes не найдено"
    fi
  fi
else
  warn "routes/web.php отсутствует на staging — skip middleware consistency"
fi

# --- Build hint ---
echo "==> Frontend build hint"
needs_build_hint=false
has_build=false
for rel_raw in "${ARGS[@]}"; do
  rel="$(normalize_path "$rel_raw")"
  case "${rel}" in
    public/build/*) has_build=true ;;
    *.blade.php|resources/js/*|resources/css/*|vite.config.*)
      needs_build_hint=true
      ;;
  esac
done
if [[ "${needs_build_hint}" == true && "${has_build}" == false ]]; then
  warn "в списке есть blade/js/css, но нет public/build/ — при необходимости: npm run build; не rsync --delete без новых ассетов"
fi

# --- Summary ---
echo "==> Summary: failures=${FAILURES}, warnings=${WARNINGS}"

if [[ "${FAILURES}" -gt 0 ]]; then
  if [[ "${FORCE}" == true ]]; then
    echo "WARN: есть нарушения, но --force задан — деплой не блокируется"
    exit 0
  fi
  echo "BLOCKED: исправьте локальные файлы (merge/cherry-pick с нужной ветки) или осознанно: --force"
  exit 1
fi

echo "OK: pre-deploy check passed"
exit 0
