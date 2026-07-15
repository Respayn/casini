#!/usr/bin/env bash
set -euo pipefail

# Восстанавливает справочники staging (integrations, products, rates, …)
# без пересоздания users/roles/permissions.
# Не трогает клиенты/проекты и не вызывает migrate:fresh.

APP_DIR="${APP_DIR:-/var/www/casini}"
MIN_INTEGRATIONS="${MIN_INTEGRATIONS:-9}"

cd "${APP_DIR}"

SEEDERS=(
  TooltipSeeder
  ProductSeeder
  ProductNotificationSeeder
  IntegrationSeeder
  AgencySettingsTableSeeder
  RatesTableSeeder
  SearchEnginesSeeder
)

echo "==> Reseed reference data in ${APP_DIR}"
for seeder in "${SEEDERS[@]}"; do
  echo "--> ${seeder}"
  php artisan db:seed --class="${seeder}" --force
done

php artisan view:clear >/dev/null 2>&1 || true

echo "==> Counts"
php artisan tinker --execute="
echo 'integrations='.App\Models\Integration::count().PHP_EOL;
echo 'products='.App\Models\Product::count().PHP_EOL;
echo 'rates='.App\Models\Rate::count().PHP_EOL;
echo 'tooltips='.Illuminate\Support\Facades\DB::table('tooltips')->count().PHP_EOL;
echo 'search_engines='.Illuminate\Support\Facades\DB::table('search_engines')->count().PHP_EOL;
" | tee /tmp/casini-reseed-counts.txt

integrations_count="$(php artisan tinker --execute="echo App\Models\Integration::count();" | tr -d '[:space:]')"

if [[ "${integrations_count}" -lt "${MIN_INTEGRATIONS}" ]]; then
  echo "FAIL: integrations=${integrations_count}, ожидалось >= ${MIN_INTEGRATIONS}"
  exit 1
fi

echo "OK: integrations=${integrations_count}"
echo "==> Reference reseed passed"
