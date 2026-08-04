# Техническая документация

## Цель
Хранить информацию о внутреннем устройстве системы, API, алгоритмах

## Правила заполнения
- Документ должен обновляться с каждым изменением, влияющим на систему.
- Фиксируем важные технические решения, даже если они кажутся очевидными сейчас.
- Если код сильно меняется, старый вариант нужно сохранять в разделе "История изменений".
- Если появились новые API, схемы БД, алгоритмы, их нужно документировать сразу.
- Используем графики, диаграммы, примеры кода для наглядности.

## Когда заполнять?
- Заполняется, когда изменяется архитектура, API или алгоритмы.
- При добавлении новых модулей, API, БД.
- При изменении логики работы существующего кода.
- Перед релизом крупного обновления.
- Если принято важное техническое решение (даже если оно еще не реализовано).

## Пример рабочего процесса
- Разработчик добавил новый API
- Перед коммитом обновил tech-doc.md (описание эндпоинтов) 
- В PR указал, что документация обновлена.

## Организация классов и потоков данных в коде

Проект следует архитектуре Clean Architecture с применением паттерна CQRS (Command Query Responsibility Segregation):

1. **Domain Layer** - содержит бизнес-логику и сущности предметной области
2. **Application Layer** - содержит Use Case'ы, реализующие конкретные сценарии использования системы
3. **Infrastructure Layer** - содержит реализации интерфейсов и интеграции с внешними системами

### Поток данных для команд (изменение данных):

```
Controller -> CommandHandler -> Domain -> Repository -> Data Source
```

### Поток данных для запросов (чтение данных):

```
Controller -> QueryHandler -> Repository -> Data Source
```

## Архитектурные принципы

### CQRS (Command Query Responsibility Segregation)

- **Команды (Commands)** - операции изменения данных (Create, Update, Delete)
- **Запросы (Queries)** - операции чтения данных (Read)
- Каждая команда/запрос имеет свой обработчик (Handler)
- Обработчики содержат логику координации между слоями

### Clean Architecture

- Четкое разделение на слои: Domain, Application, Infrastructure
- Зависимости направлены внутрь (Dependency Rule)
- Domain слой не зависит от фреймворка
- Использование интерфейсов для инверсии зависимостей

## Пример реализации Use Case

Для реализации нового функционала следуйте шаблону:

1. **Domain Layer**:
   - Создать сущность в `src/Domain/{Entity}/{EntityName}.php`
   - Создать интерфейс репозитория в `src/Domain/{Entity}/{EntityName}RepositoryInterface.php`

2. **Application Layer**:
   - Создать команду/запрос в `src/Application/{Feature}/{Operation}/{Operation}{Entity}Command.php`
   - Создать обработчик в `src/Application/{Feature}/{Operation}/{Operation}{Entity}CommandHandler.php`
   - Создать DTO для передачи данных в `src/Application/{Feature}/Data/{Entity}Data.php`

3. **Infrastructure Layer**:
   - Создать реализацию репозитория в `src/Infrastructure/Persistence/{Entity}Repository.php`

## Тестирование

- **Unit-тесты** для доменной логики в `tests/Unit/Domain/`
- **Feature-тесты** для прикладной логики в `tests/Feature/Application/`
- **Интеграционные тесты** в `tests/Feature/Integration/`
- Feature-тесты с БД используют `DatabaseTransactions` (откат данных, схема и seed staging не трогаются).
- `RefreshDatabase` / `migrate:fresh` блокируются в `tests/TestCase.php` через `PreventsDestructiveDatabaseRefresh`, если не задан `CASINI_ALLOW_DB_REFRESH=true` **и** имя БД не оканчивается на `_test`. БД `casini` всегда защищена.
- После деплоя на staging: `bash scripts/staging-smoke.sh`.
- После `php artisan` / `composer` от root: `bash scripts/staging-fix-permissions.sh` (права `storage/` и `bootstrap/cache/` для `www-data`).
- После wipe БД / пустых модалок интеграций: `bash scripts/staging-reseed-reference.sh` (справочники без пересоздания users).

## Справочники staging (reference data)

Список типов интеграций в UI клиенто-проекта читается из таблицы `integrations` (`IntegrationService::getIntegrations()`), не из blade-файлов. Ожидается ≥ 9 записей (`IntegrationSeeder`: 1С ×3, Yandex Search API, Google Sheets, Мегаплан, Яндекс.Директ, Яндекс.Метрика, Callibri).

Другие обязательные справочники: `products`, `product_notifications`, `rates`, `tooltips`, `search_engines`, агентство (`AgencySettingsTableSeeder`).

Сидер `IntegrationSeeder` / `ProductSeeder` / `TooltipSeeder` / `ProductNotificationSeeder` — идемпотентны (`updateOrInsert` по `code`). Восстановление: `scripts/staging-reseed-reference.sh`. Smoke проверяет `integrations.count > 0`.

## Livewire compiler cache и права storage

Livewire 4 компилирует multi-file components (MFC) в `storage/framework/views/livewire/` (`classes/`, `views/` и т.д.). Запись идёт через `File::replace()` → `tempnam()` в том же каталоге.

Если каталог создан от `root` (типично после `php artisan test` / `optimize` от root на staging), PHP-FPM (`www-data`) не может писать → PHP 8.3 бросает `ErrorException: tempnam(): file created in the system's temporary directory` на страницах вроде `/system-settings/dictionaries`.

Исправление: `bash scripts/staging-fix-permissions.sh --clear`. Профилактика: runtime-artisan только как `sudo -u www-data php artisan ...`.

## Авторизация

- Livewire-страница: `resources/views/pages/auth/login/`.
- Логика входа: `App\Services\Auth\LoginService` — поиск по `login` или `email` (case-insensitive), проверка пароля, проверка `is_active`, затем `Auth::login`.
- Переводы: `resources/lang/ru/auth.php` (`failed`, `inactive`).
- Пароли: в Eloquent передаётся plain-text; cast `password => hashed` в `User` хеширует при сохранении. Не вызывать `Hash::make` / `bcrypt` перед записью в модель.

## Интеграция Яндекс.Директ (настройки клиенто-проекта)

### Переменные окружения (OAuth)

| Переменная | Назначение |
|------------|------------|
| `YANDEX_DIRECT_CLIENT_ID` | Client ID OAuth-приложения Яндекса |
| `YANDEX_DIRECT_CLIENT_SECRET` | Client Secret |
| `YANDEX_DIRECT_REDIRECT_URI` | Callback URL, должен **буквально** совпадать с URI в консоли OAuth (напр. `https://test.casini.ru/yandex-direct/callback`) |

Если `YANDEX_DIRECT_CLIENT_ID` или `YANDEX_DIRECT_CLIENT_SECRET` пусты, при включении синхронизации Яндекс OAuth отвечает **400** («Отсутствует обязательный параметр client_id»). В UI модалка показывает предупреждение; `prepareYandexDirectOAuth()` возвращает `error` без URL.

Проверка на сервере:

```bash
php artisan config:clear
php artisan tinker --execute="echo config('services.yandex_direct.client_id') ? 'OK' : 'EMPTY';"
```

### OAuth popup

1. В модалке пользователь включает «Синхронизация» → Livewire `prepareYandexDirectOAuth($popup)` генерирует UUID `cache_data_id`, сохраняет черновик в Cache (`integration_data_{uuid}`) и возвращает `{ url, cache_data_id }`.
2. Alpine:
   - обычный браузер — открывает **один** popup (`window.open`);
   - Cursor/Electron/iframe или заблокированный popup — **redirect** (`window.location`, `popup=0`).
3. Authorize URL всегда с **`force_confirm=yes`** — Яндекс показывает выбор аккаунта и подтверждение доступа (без silent auth).
4. Pending-сессия хранится в **`localStorage`** (`casini_yandex_direct_oauth`); popup-complete дополнительно пишет `casini_yandex_direct_oauth_done` **с тем же** `cacheDataId`. Coordinator применяет DONE только если он совпадает с pending; при новом старте DONE сбрасывается.
5. Callback (`YandexDirectOAuthController`):
   - `popup=1`: Cache `yandex_direct_oauth_result_{id}` + view `oauth/yandex-direct-popup-complete` (BroadcastChannel + postMessage + localStorage);
   - `popup=0`: redirect на форму проекта со `state` + `open_integration=yandex_direct` (модалка открывается в `mount`). **`client_login` не заполняется** — выбор вручную.
6. Координатор в layout `system-settings` (`x-scripts.yandex-direct-oauth-coordinator`): BroadcastChannel / postMessage / `storage` / polling → `Livewire.getByName(...).finalize|apply…` или `Livewire.dispatchTo('yandex-direct-oauth-received')`.
7. После apply: `$integrationModalBodyRevision++`, серверный `loadYandexDirectLogins`, browser-event `yandex-direct-oauth-applied` (Alpine обновляет token + loginOptions **без** автовыбора login), `wire:key` remount, `modal-show`.

**UI логинов:** inline Alpine-select в модалке (тот же `x-data`, без `parentOptionsKey`). Пользователь обязан выбрать Client-Login вручную.

**Morph / Alpine:** тело модалки Директа с `wire:ignore` для OAuth-start; после apply смена `wire:key` (+ revision) даёт свежий UI.

### Общая модалка интеграций (remount)

Одна модалка [`integration-settings-modal`](resources/views/components/project-form/integration-settings-modal.blade.php) для Callibri, Яндекс.Директ, Yandex Search API и будущих интеграций (Метрика и др.).

| Механизм | Назначение |
|----------|------------|
| `$integrationModalBodyRevision` | Счётчик remount; `++` в `selectIntegration()` и при apply OAuth Директа (аналогично — при apply других OAuth-интеграций) |
| `wire:key` на обёртке body | `integration.code` + id + hash settings + revision — **без** `wire:ignore` на обёртке |
| `wire:key` на обёртке sidebar | `integration.code` + revision |

**Правило для новых интеграций:** body = `project-form.{kebab-code}-integration-modal-body` + sidebar. Если нужен `wire:ignore` (Alpine/OAuth) — только на **внутреннем** корне body, не на обёртке с `wire:key`. Иначе при переключении интеграций тело «залипает» на предыдущей.

**Callback errors:** при `invalid_grant` popup показывает сообщение и шлёт error через BroadcastChannel / `postMessage({ type: 'yandex-direct-oauth-error' })`.

### Список логинов

**Base URL API (JSON):** `https://api.direct.yandex.com/json/v5/` (sandbox: `https://api-sandbox.direct.yandex.com/json/v5/`). Legacy `…/v5/json/` и `…/v5/` нормализуются в `YandexDirectService::normalizeDirectApiBaseUrl()`.

Для OAuth с **реальными** аккаунтами (staging/prod) нужны `YANDEX_DIRECT_USE_SANDBOX=false` и prod `YANDEX_DIRECT_API_URL=…/json/v5/` — sandbox API не отдаёт клиентов реального агентства.

`YandexDirectService::resolveClientLogins($token)` (используется из `loadYandexDirectLogins`; `listClientLogins` — обёртка только над `logins`):

1. POST `agencyclients` **без** `Client-Login`: `SelectionCriteria.Archived=NO`, `FieldNames: Login, ClientInfo`, пагинация `Page.Limit=10000` / `Offset` ← `result.LimitedBy` — дочерние логины клиентов агентства.
2. Если список непустой — отдаём его (дедуп по Login, сортировка по ClientInfo/Login).
3. Иначе POST `clients` (Type, Login):
   - `Type=AGENCY` — **без** fallback на OAuth-логин; ошибка «не удалось получить список клиентов агентства» (или «нет активных клиентов», если AgencyClients вернул пустой успех).
   - иначе — Login из Clients.get.
4. Если Clients.get вернул Type ≠ AGENCY без Login — fallback `login.yandex.ru/info` → один логин (прямой рекламодатель).
5. Если **оба** AgencyClients и Clients недоступны (HTTP/ошибка) — ошибка про `YANDEX_DIRECT_*_API_URL` / sandbox, **без** OAuth-логина.

Вызов при apply OAuth и при `init()`/`loadLogins()` модалки. `client_login` пользователь выбирает вручную.

### UI ошибок и удаление

- Баннер ошибки (`oauthError` / `loginsError`) — под заголовком модалки, стиль как info-блок Callibri, цвет `#FF7373`.
- При ошибке: ползунок «Синхронизация» и рамка select «Логин» — CSS-классы `yd-direct-error-toggle` / `yd-direct-error-login` (токены `--color-integration-error*` в `app.css`, не Tailwind arbitrary).
- После успешного `loadLogins` ошибки сбрасываются.
- «Удалить интеграцию» (`titleActions`) видна, если есть `oauth_token` **или** `client_login` **или** `is_enabled` (не только полностью настроенная интеграция).

### OAuth-профиль Яндекс ID

После OAuth в settings сохраняется профиль **Passport-аккаунта** (кто нажал «Разрешить»), отдельно от `client_login` (рекламодатель в Директе):

**OAuth scope в redirect** (`YandexDirectOAuthController::redirect`): `login:email`, `login:info`, `login:avatar`, `direct:api`. Право должно быть включено в консоли OAuth-приложения; в URL авторизации scope **обязательно** запрашивать явно — иначе токен не содержит нужных полей в `login.yandex.ru/info`. Для аватарки критичен `login:avatar` (`default_avatar_id`); для имени — `login:info` (`display_name`). После смены scope интеграции с уже выданным токеном нужен повторный OAuth.

| Ключ | Назначение |
|------|------------|
| `oauth_yandex_user_id` | ID пользователя Яндекса (`login.yandex.ru/info` → `id`) |
| `oauth_yandex_login` | Логин Яндекса |
| `oauth_yandex_display_name` | Отображаемое имя |
| `oauth_yandex_avatar_url` | URL аватарки (`default_avatar_id` → `avatars.yandex.net/get-yapic/…/islands-200`) |

Источник: `YandexDirectService::fetchOauthUserProfile()` при callback и `loadYandexDirectOAuthProfile()` для backfill старых интеграций (в т.ч. при битом avatar URL с `%2F`).

**UI карточки:** стиль info-блока Callibri (`border-primary bg-blue-50`); строка `@логин` показывается только если `display_name` задан и отличается от login; кнопка «Выбрать другую учетную запись» вызывает повторный OAuth (`force_confirm=yes`) без выключения синхронизации.

**Avatar URL:** `https://avatars.yandex.net/get-yapic/{default_avatar_id}/islands-200` — slash в `default_avatar_id` **не** кодируется (`rawurlencode` давал 404).

Legacy `account_id` (раньше ошибочно писался `client_id` OAuth-приложения) не используется для UI.

### Settings (snake_case)

| Ключ | Назначение |
|------|------------|
| `client_login` | Client-Login для API Директа |
| `oauth_token` / `refresh_token` / `token_expires_at` | OAuth |
| `oauth_yandex_*` | профиль Яндекс ID (см. выше) |
| `sync_enabled_at` | дата включения синхронизации (`Y-m-d`) |

Чтение поддерживает legacy camelCase (`clientLogin`, `encryptedOauthToken`).

## Каналы: остаток бюджета и расход в Директе

Колонки `direct-budget` / `direct-spendings` на странице `/channels`.

Период отчёта: `ChannelReportQueryData.dateFrom` / `dateTo` (месяц–месяц), UI — два `x-form.month-picker` с `disable-future`. По умолчанию оба = текущий месяц. Будущие месяцы запрещены (UI + `clampPeriodToPresent()`).

| Что | Как |
|-----|-----|
| Источник credentials | `integration_project.settings` проекта: `oauth_token` + `client_login` (код интеграции `yandex_direct`) |
| Остаток | `YandexDirectService::getAccountBalance()` (API v4) — только «сейчас»; кэш Laravel `channels.direct.budget.{projectId}` = `{value, updated_at}` (TTL 7 дней); в ячейке `ЧЧ:мм, дд.мм.гг / сумма ₽` (время по `agencies.time_zone`, дата — `text-secondary-text`); обновление только bulk |
| План | только если `dateFrom` и `dateTo` в одном месяце; иначе в ячейке `-` |
| Расход | сумма дней из `yandex_direct_daily_spendings` за `dateFrom`…`dateTo` (до сегодня для текущего месяца; колонка с/без НДС). Ночной съём + bulk refresh через `YandexDirectDailySpendCollector` |
| Обновление | только массовые действия `refresh_budget_remains` / `refresh_spendings` (клик по ячейке отключён) |
| Сервис UI | `ChannelDirectMetricsService`; строки — `ChannelReportService::enrichWithDirectMetrics()` |

Пока расхода нет в БД, в ячейке `-`.

### Лимит ручных запросов к API Директа (и образец для Статистики)

Класс: `App\Services\Channels\ChannelDirectApiThrottle`. Правила зафиксированы в `.cursor/rules/casini-project-workflow.mdc` (раздел «Ручные запросы к внешнему API»).

| Параметр | Значение |
|----------|----------|
| Интервал | ≥ 5 минут между запросами |
| Серия | ≤ 3 запроса подряд |
| После серии | блок 60 минут |
| Ключ кэша | `channels.direct.api_throttle.user.{userId}` |

Массовое действие = один `consume()`.

## Статистика: период отчёта

Страница `/statistics`. Период как в Каналах: `StatisticsReportQueryData.dateFrom` / `dateTo` (месяц–месяц), UI — два `x-form.month-picker` с `disable-future`. По умолчанию оба = текущий месяц. Будущие месяцы запрещены (UI + `clampPeriodToPresent()`).

| Что | Как |
|-----|-----|
| План | только если `isSingleMonthPeriod()`; иначе в ячейке `-` |
| Колонки детализации (день / неделя / месяц) | при одном месяце — этот месяц; при интервале — по `dateTo` (`detailGridMonth()`). Полная детализация по нескольким месяцам — отдельно |

## Ночной съём интеграций (единый каркас)

Правила съёма (полночь по `agencies.time_zone`, вчерашний день, только активные проекты, requeue при ошибке API) — этот раздел; локально для агента также может лежать `.cursor/rules/integration-data-sync.mdc` (в git не коммитится).

| Компонент | Назначение |
|-----------|------------|
| `agencies.time_zone` | «Основной часовой пояс агентства»; окно старта **00:01** локально |
| `php artisan integrations:dispatch-due-syncs` | Schedule `everyMinute()`: если 00:01 и нет run за текущую local-дату → создать run + items за **вчера** |
| `integration_sync_runs` / `integration_sync_items` | run и очередь проектов; при ошибке API item → в хвост, max 3 attempts |
| `ProcessIntegrationSyncItem` | Job: один item → collector → upsert |
| `yandex_direct_daily_spendings` | `project_id`, `date`, `cost_with_vat`, `cost_without_vat`, unique `(project_id, date)` |

Кандидаты: `projects.is_active` + enabled `yandex_direct` с token и client_login.

Staging: cron `schedule:run` + Supervisor `queue:work`.
