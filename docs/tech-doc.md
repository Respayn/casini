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

## Права разделов настроек системы

Продукт «Настройки» разделён на независимые группы Spatie Permission:

| Группа (value) | Раздел UI / URL |
|----------------|-----------------|
| `system settings` | Настройки агентства `/system-settings/agency` |
| `system settings dictionaries` | Справочники |
| `system settings users` | Пользователи и роли |
| `system settings roles and permissions` | Продукты и права |

Уровни: `read` / `edit` / `full` + имя группы.

**Миграция ролей со старого единого права:** после `PermissionSeeder` на стенде с уже выданными `* system settings` выполнить:

```bash
php artisan db:seed --class=PermissionSeeder --force
php artisan db:seed --class=MigrateSystemSettingsPermissionsSeeder --force
```

Seeder копирует read/edit/full с `system settings` на три новых группы; право на агентство не снимает. Admin получает новые permissions через `PermissionSeeder`. Seeder **не** в `DatabaseSeeder` — одноразовый перенос.

**Свой профиль:** маршрут `system-settings.users.edit` для `user.id === auth()->id()` доступен любому авторизованному (middleware `EnsureCanAccessUserEdit`). Список пользователей — с `read|edit|full system settings users`; создание (`/users/create`) — только с `edit|full system settings users` (кнопка «+ Добавить пользователя» disabled без edit). Поля логин / статус / роль / ставка / Мегаплан редактируются только при `edit|full system settings users`; без этого права — disabled в UI и отсекаются в `UserProfileAccess::mergeSavePayload`.

**Продукты и права:** `read` открывает страницу; `edit|full` — изменение. Без edit UI в режиме read-only: кнопки, ссылки, чекбоксы и переключатели disabled, при наведении — `permissions.denied` (`field-guard` / Alpine-тултип). `save()` без edit бросает `UnauthorizedException`.

**Роль по умолчанию** (`default`): системная роль для регистрации / приглашений. Seed: `read channels`, `read statistics`, `read reports`, `read planning`, `read clients and projects self`. Удаление запрещено (UI + `RoleRepository`). На «Продукты и права» у этой роли и у **Администратора** скрыты блоки «Собрать портфель…» и «Подчинённые»; у default все чекбоксы locked (пять read всегда включены и disabled, остальные выкл.) с `permissions.default_role_locked`. При save права всегда восстанавливаются в `DefaultRole::grantedPermissionNames()`. Переименование **Администратора** тоже запрещено (`permissions.admin_role_name_locked`).

**Шестерёнка в header:** показывается, если есть чтение хотя бы одного из пяти продуктов (Продукты и права, Пользователи и роли, Клиенты и клиенто-проекты, Справочники, Настройки агентства). Ссылка — первый доступный раздел в том же порядке (`SystemSettingsSectionPermissions::firstAccessibleSettingsRouteName`).

## Клиенты и клиенто-проекты (доступ)

Маршруты списка и формы проекта: middleware `ClientsAndProjectsPermissions` — любое из read|edit|full для родителя `clients and projects`, `… self`, `… all`.

- Список фильтруется `ClientListVisibilityFilter` (self: менеджер клиента / specialist проекта; all: всё).
- Создание и сохранение клиента/проекта — `ensureUserCanEdit` (edit|full self|all).
- Открытие существующего проекта — `ClientProjectAccessPolicy` (all или self с привязкой).
- **Форма клиенто-проекта (read-only):** при `read` без `edit|full` форма открывается на просмотр — все поля, toggles, кнопки и интеграции disabled + тултип `permissions.denied` (`x-permissions.field-guard`). Кнопка «Сохранить» disabled. Серверная защита: `ensureCanEdit()` на всех публичных мутациях (`save`, `addRegion`, `removeRegion`, `addTopic`, `removeTopic`, `addInterval`, `removeInterval`, `addMapping`, `removeMapping`, `selectIntegration`, `setIntegrationSettings`, `removeIntegration`, `setIntegrationEnabled`, OAuth-методы, `loadCallibriProjects`, `testCallibriIntegration`, `loadYandexMetrikaGoals`, `loadYandexMetrikaSearchEngines`, `testYandexMetrikaGoalsSearchEnginesIntegration`, `parsePhrasesFromDocx`). Модалки интеграций (Callibri, Яндекс.Директ, Search API) также заблокированы через Alpine `canEdit` + серверный guard. Восстановление OAuth state из cache при mount пропускается для read-only.

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

## Форма клиенто-проекта: помощники

Несколько помощников проекта хранятся в pivot-таблице `project_assistant` (`project_id`, `user_id`, уникальная пара). Связь: `Project::assistants()` → `belongsToMany(User)`. Синхронизация при сохранении — `ProjectService::updateOrCreateProject` через `assistantIds` в `ProjectData`.

Списки «Специалист» / «Помощник» в форме показывают всех пользователей агентства (`UserService::getByAgency`), подпись `Имя Фамилия (должность)`. Проверка прав редактирования формы временно всегда разрешена; полноценный read-only UI — после merge `feature/roles-permissions`.

## Форма клиенто-проекта: схема расчёта параметров

Блок «Настройка параметров» → «Схема расчета параметра» собирается автоматически сервисом `App\Services\ClientProject\ParameterCalculationSchemeBuilder` из **включённых** интеграций проекта (`integrationSettings` с `isEnabled = true`).

| KPI / тип | Параметры | Источники |
|---|---|---|
| Контекст + Traffic | CPC, бюджет, визиты | Яндекс Директ (расходы / клики) |
| Контекст + Leads | CPL, бюджет, лиды | Директ (расходы); Callibri ЕЖЛ и/или Метрика (цели UTM; цели «Поисковые системы» — этап 3) |
| SEO + Positions | % в топ 10, конверсии | Yandex Search API; схема в коде ещё пишет «цели Поисковые системы», в UI этот отчёт Метрики доступен только для Контекста |
| SEO + Traffic | объём визитов, конверсии | Метрика (переходы «Поисковые системы»); цели в UI — только для Контекста |

**Yandex Search API не влияет** на CPL, рекламный бюджет и лиды. Если для параметра нет подходящей интеграции — текст `Не настроено`. Длинные схемы обрезаются с `…`, полный текст в `title`.

Пересчёт на форме сразу после `setIntegrationSettings` / `removeIntegration` / `setIntegrationEnabled` / OAuth Директа (без сохранения всей формы).

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

Одна модалка [`integration-settings-modal`](resources/views/components/project-form/integration-settings-modal.blade.php) для Callibri, Яндекс.Директ, Yandex Search API и Яндекс Метрики.

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

**OAuth scope в redirect** (`YandexDirectOAuthController::redirect`): `login:info`, `login:avatar`, `direct:api`. Право должно быть включено в консоли OAuth-приложения; в URL авторизации scope **обязательно** запрашивать явно — иначе токен не содержит нужных полей в `login.yandex.ru/info`. Почту и телефон не запрашиваем (`login:email` / `login:phone`) — иначе Яндекс отвечает `invalid_scope`. Для аватарки критичен `login:avatar` (`default_avatar_id`); для имени — `login:info` (`display_name`). После смены scope интеграции с уже выданным токеном нужен повторный OAuth.

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

## Интеграция Яндекс Метрики (настройки клиенто-проекта, этап 1)

Модалка в карточке «Аналитика». На этапе 1 сохраняются настройки и OAuth. Разбор фильтров в параметр API — этап 2 (ниже). Съём отчёта «Поисковые системы / цели» — этап 3.

### Переменные окружения (OAuth)

| Переменная | Назначение |
|------------|------------|
| `YANDEX_METRIKA_CLIENT_ID` | Client ID OAuth-приложения Яндекса (отдельное от Директа) |
| `YANDEX_METRIKA_CLIENT_SECRET` | Client Secret |
| `YANDEX_METRIKA_REDIRECT_URI` | Callback, должен совпадать с URI в консоли (напр. `https://test.casini.ru/yandex-metrika/callback`) |

Токен Директа для Метрики не подходит: разные приложения и scope. Аккаунт Яндекса может быть тем же.

Если `CLIENT_ID` / `CLIENT_SECRET` пусты, `prepareYandexMetrikaOAuth()` возвращает `error` без URL; popup показывает «не настроена на сервере».

### OAuth popup

Тот же контур, что у Директа, но отдельные ключи Cache / localStorage / BroadcastChannel:

1. Ползунок «Синхронизация» → Livewire `prepareYandexMetrikaOAuth($popup)` → UUID `cache_data_id`, черновик в `integration_data_{uuid}`, URL `yandex-metrika.auth`.
2. Alpine: popup или redirect (Cursor/Electron/iframe).
3. Authorize URL: `force_confirm=yes`, scope `login:info login:avatar metrika:read`.
4. Callback (`YandexMetrikaAuthController`) **не пишет в БД**. `popup=1`: Cache `yandex_metrika_oauth_result_{id}` + view popup-complete. `popup=0`: redirect на форму со `open_integration=yandex_metrika`. **`counter_id` не заполняется** — выбор вручную.
5. Coordinator в layout `system-settings` (`x-scripts.yandex-metrika-oauth-coordinator`).
6. После apply: список счётчиков `GET management/v1/counters`, событие `yandex-metrika-oauth-applied`.

**UI счётчика:** `{id} ({домен})`. Поле disabled, пока нет OAuth-токена.

### Settings (snake_case)

| Ключ | Назначение | По умолчанию |
|------|------------|--------------|
| `oauth_token` / `refresh_token` / `token_expires_at` | OAuth | — |
| `oauth_yandex_*` | профиль Яндекс ID | — |
| `sync_enabled_at` | дата включения синхронизации | — |
| `counter_id` / `counter_domain` | выбранный счётчик | — |
| `counter_time_zone` | IANA-пояс счётчика (`time_zone_name` из Management API) | — |
| `attribution_model` | значение из справочника `AttributionModel` (в API — `trim`) | `automatic` |
| `data_mode` | `without_robots` / `with_robots` | `without_robots` |
| `filters.entry_page` | текст фильтра страницы входа (`!` = исключение) | `null` |
| `filters.last_search_phrase` | последняя значимая поисковая фраза | `null` |
| `filters.geo` | география | `null` |
| `reports.*` | какие отчёты подтягивать | все `false` |
| `goals` | ID выбранных целей счётчика | `[]` |
| `goals_metric` | `target_visits` (Целевые визиты) или `goal_reaches` (Достижения цели) | `target_visits` |
| `search_engines_all` | режим «Все поисковые системы» для отчёта «Переходы» | `true` |
| `search_engines` | root-ID выбранных ПС (`yandex`, `google`…) при `search_engines_all=false` | `[]` |
| `search_queries_minus` | минус-фразы для отчёта «Поисковые запросы» (каждая с новой строки) | `''` |
| `visits_metric` | `visits` (Визиты) или `users` (Посетители) для отчётов «Переходы» | `visits` |

Ключи `reports`: `goals_search_engines`, `goals_utm`, `goals_conversions`, `goals_direct_summary`, `visits_search_engines`, `visits_search_queries`, `visits_geo`. Из четырёх источников целей (`goals_search_engines` / `goals_utm` / `goals_conversions` / `goals_direct_summary`) в UI можно выбрать только один — остальные disabled с тултипом «Может быть выбран только один источник достижения целей». `visits_search_engines`, `visits_search_queries` и `visits_geo` доступны только при типе клиенто-проекта `seo_promotion` (SEO-продвижение); иначе disabled с тултипом «Доступен только для клиенто-проектов с типом SEO-продвижение». `goals_search_engines` доступен только при типе `seo_promotion` (SEO-продвижение); иначе disabled с тултипом «Доступен только для клиенто-проектов с типом SEO-продвижение». Если этот отчёт включён, нужны выбранные цели и параметр `goals_metric`.

### Фильтры в запросах к API (этап 2)

Текст из модалки собирает [`YandexMetrikaFiltersBuilder`](src/Domain/YandexMetrika/YandexMetrikaFiltersBuilder.php) в параметр `filters` Reporting API. Пустое поле (или фильтр не добавляли) в запрос не попадает. [`YandexMetrikaService::getVisitsReport()`](app/Services/YandexMetrikaService.php) и `getGoalAchievements()` подставляют эту строку автоматически.

Правила как в тултипе модалки: каждое условие с новой строки; `!` в начале строки — отрицание (НЕ); утверждения внутри одного поля соединяются через **ИЛИ**; отрицания — через **И**; разные поля и «Без роботов» — через **И**.

| Поле | Группировка API |
|------|-----------------|
| `filters.entry_page` | `ym:s:startURL` |
| `filters.last_search_phrase` | `ym:s:lastsignSearchPhrase` |
| `filters.geo` | `ym:s:regionCityName` **или** `ym:s:regionCountryName` **или** `ym:s:regionAreaName` (чтобы сработали и город, и страна) |
| `data_mode=without_robots` | `ym:s:isRobot=='No'` |
| `data_mode=with_robots` | кусок про роботов не добавляется |

Операторы: если в тексте есть `*` (как `!*promo*`) — шаблон `=*` / `!*`; если звёздочек нет (как `catalog`) — «содержит» `=@` / `!@`. В значении экранируются `'` и `\`.

Пример страницы входа `catalog` + `store` + `!*promo*`:

`(ym:s:startURL=@'catalog' OR ym:s:startURL=@'store') AND ym:s:startURL!*'*promo*'`

### Часовой пояс в запросах к API

Callibri отдаёт каждое обращение с временем в UTC — Касини переводит его в пояс агентства и решает, в какой день оно попало. Reporting API Метрики считает сразу по календарным дням (`date1` / `date2`). Если не передать `timezone`, Метрика берёт **пояс счётчика** (часто Москва), и сутки в Касини могут разъехаться с интерфейсом Метрики.

Поэтому [`YandexMetrikaService`](app/Services/YandexMetrikaService.php) передаёт параметр `timezone` (`±hh:mm` из пояса агентства) **только если** смещение агентства отличается от пояса счётчика (`counter_time_zone`). Если пояса совпадают (например оба Екатеринбург `+05:00`) или пояс счётчика ещё не сохранён — параметр не шлём, API сам берёт пояс счётчика. Плюс в query кодируется как `%2B` (`PHP_QUERY_RFC3986`), иначе `+05:00` превратится в пробел.

Чтобы цифры совпали с интерфейсом Метрики, пояс агентства должен совпадать с поясом счётчика — об этом напоминает синий блок в модалке (как у Callibri).

## Интеграция Яндекс Метрики (этап 3.1: цели «Поисковые системы»)

Первый отчёт этапа 3.

### UI

Если выбран `goals_search_engines` (и тип проекта — SEO-продвижение):

Поля этапа 3 вставляются сразу после первого отчёта, остальные шесть отчётов идут ниже. Чекбоксы отчётов справа от названия.

1. «Выберите цели, по которым хотите получать статистику» — чекбоксы `{название} (№{номер})` в рамке, видно 4 строки, остальные за скроллом. Список грузит Livewire `loadYandexMetrikaGoals()` (`GET management/v1/counter/{id}/goals`).
2. «По какому параметру рассчитываем достижение целей?» — `target_visits` (Целевые визиты, по умолчанию) или `goal_reaches` (Достижения цели).
3. Ссылка «Проверить работу интеграции» (без стрелки и без кнопки «Проверить») открывает поля: дата (`ДД.ММ.ГГ`) и disabled «Количество достижений цели». Запрос уходит при выборе даты. Livewire: `testYandexMetrikaGoalsSearchEnginesIntegration()`.

Сохранить без выбранных целей нельзя (`canSave` + серверная проверка в `setIntegrationSettings`).

### API

[`YandexMetrikaService::fetchSearchEnginesGoalsStats()`](app/Services/YandexMetrikaService.php) запрашивает Reporting API `stat/v1/data` как отчёт [«Поисковые системы»](https://yandex.ru/support/metrica/ru/sources/search-engines.html) / preset `search_engines`:

- группировка: `ym:s:<attribution>SearchEngineRoot` (один месяц) или `ym:s:<attribution>SearchEngineRoot,ym:s:month` (несколько месяцев);
- фильтр источника: `ym:s:<attribution>TrafficSource=='organic'`;
- метрики: `ym:s:goal{ID}visits` или `ym:s:goal{ID}reaches` по выбранным целям;
- фильтры этапа 2, timezone и `attribution` — как обычно.

Названия ПС нормализуются в `yandex` / `google` / `other` ([`YandexMetrikaSearchEngine`](app/Support/YandexMetrikaSearchEngine.php)). Проверка за день суммирует все строки.

### Ночной съём

Команда `metrika:sync-search-engines-goals` (расписание `03:00` в [`routes/console.php`](routes/console.php)):

- проекты с включённой Метрикой, `reports.goals_search_engines`, токеном, счётчиком, целями и `sync_enabled_at`;
- период: с начала месяца `sync_enabled_at` по сегодня;
- запись в `yandex_metrika_search_engines_stats.conversions` без затирания `visits`.

Ошибка по одному проекту не останавливает остальные.

## Интеграция Яндекс Метрики (этап 3.2: цели «UTM-метки»)

Второй отчёт этапа 3. UI, API и ночной съём — по шаблону этапа 3.1.

### Дополнительные ключи settings

| Ключ | Значения | По умолчанию |
|------|----------|--------------|
| `utm_filter_mode` | `source` / `medium` / `campaign` | `source` |
| `utm_source` | строка (значения через запятую) | `''` |
| `utm_medium` | строка | `''` |
| `utm_campaign` | строка | `''` |

`goals` и `goals_metric` общие для всех четырёх источников целей (exclusive-логика в Alpine — одновременно включён только один).

### UI

При включённом `goals_utm` блок обёрнут рамкой (`border-primary/30`) с полями:

1. «Выберите цели…» — общий список с `goals_search_engines`.
2. «По какому параметру…» — `target_visits` / `goal_reaches`.
3. «С каких UTM-меток…» — выпадающий список: `source` (default), `medium`, `campaign`.
4. Условное текстовое поле «Какие цели забираем с меткой UTM_*?» с тултипом.
5. «Проверить работу интеграции» — по шаблону Callibri. Livewire: `testYandexMetrikaGoalsUtmIntegration()`.

Сохранить без целей нельзя (`canSave` + серверная валидация).

### API

[`YandexMetrikaService::fetchUtmGoalsStats()`](app/Services/YandexMetrikaService.php):

- как отчёт [«Метки UTM»](https://yandex.ru/support/metrica/ru/reports/tags-utm.html) / preset `tags_u_t_m`;
- группировка: `ym:s:<attribution>UTM{Source|Medium|Campaign},ym:s:date` (под выбранную атрибуцию);
- фильтр UTM строит [`YandexMetrikaUtmFilterBuilder`](src/Domain/YandexMetrika/YandexMetrikaUtmFilterBuilder.php): пустое поле → «не пусто» на том же attribution-измерении, значения через запятую → OR с `=@` / `=*`;
- метрики: `ym:s:goal{ID}visits` / `ym:s:goal{ID}reaches`;
- фильтры этапа 2, timezone, attribution — как обычно.

### Ночной съём

Команда `metrika:sync-utm-goals` (расписание `03:30` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.goals_utm`, токен, счётчик, цели, `sync_enabled_at`;
- стратегия: удаляет старые строки за период и вставляет свежие в `yandex_metrika_goal_utms`.

## Интеграция Яндекс Метрики (этап 3.3: цели «Конверсии»)

Третий отчёт этапа 3. UI, API и ночной съём — по шаблону этапа 3.1.

### Ключи settings

Новых ключей нет. Используются общие `goals` и `goals_metric`.

`goals_conversions` **без** ограничения по типу проекта (в отличие от `goals_search_engines`).

### UI

При включённом `goals_conversions` блок обёрнут рамкой (`border-primary/30`) с полями:

1. «Выберите цели…» — общий список с `goals_search_engines` и `goals_utm`.
2. «По какому параметру…» — `target_visits` / `goal_reaches`.
3. «Проверить работу интеграции» — по шаблону Callibri. Livewire: `testYandexMetrikaGoalsConversionsIntegration()`.

Сохранить без целей нельзя (`canSave` + серверная валидация).

### API

[`YandexMetrikaService::fetchConversionsGoalsStats()`](app/Services/YandexMetrikaService.php):

- группировка: `ym:s:goal` (один месяц) или `ym:s:goal,ym:s:month` (несколько месяцев) — как preset `conversion` ([отчёт «Конверсии»](https://yandex.ru/support/metrica/ru/reports/conversion.html));
- метрики: `ym:s:goal{ID}visits` / `ym:s:goal{ID}reaches` по выбранным целям («Целевые визиты» / «Достижения цели»);
- атрибуция на итог цели не влияет (проверено: automatic/lastsign/last/first дают одно число);
- в ответе оставляем только строки с `dimensions[0].id` из выбранных целей;
- имя цели из `dimensions[0].name`;
- фильтры этапа 2, timezone, attribution — как обычно.

### Ночной съём

Команда `metrika:sync-conversions-goals` (расписание `04:00` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.goals_conversions`, токен, счётчик, цели, `sync_enabled_at`;
- стратегия: upsert по unique `(project_id, goal_name, month)` в `yandex_metrika_goal_conversions`.

## Интеграция Яндекс Метрики (этап 3.4: цели «Директ, сводка»)

Четвёртый отчёт этапа 3. UI, API и ночной съём — по шаблону этапа 3.3 (Конверсии).

### Ограничение по типу проекта

`goals_direct_summary` доступен только для типа «Контекстная реклама» (`context_ad`). Реализовано через `$contextOnlyGoalReportKeys` — зеркало `$seoOnlyGoalReportKeys` для SEO.

### Ключи settings

Новых ключей нет. Используются общие `goals` и `goals_metric`.

### UI

При включённом `goals_direct_summary` блок обёрнут рамкой (`border-primary/30`) с полями:

1. «Выберите цели…» — общий список.
2. «По какому параметру…» — `target_visits` / `goal_reaches`.
3. «Проверить работу интеграции» — по шаблону Callibri. Livewire: `testYandexMetrikaGoalsDirectSummaryIntegration()`.

Сохранить без целей нельзя (`canSave` + серверная валидация `$needsGoals`).

### API

[`YandexMetrikaService::fetchDirectSummaryGoalsStats()`](app/Services/YandexMetrikaService.php):

- группировка: `ym:s:goal` (один месяц) или `ym:s:goal,ym:s:month` (несколько месяцев);
- метрики: `ym:s:goal{ID}visits` / `ym:s:goal{ID}reaches` по выбранным целям;
- сегмент как у отчёта [«Директ, сводка»](https://yandex.ru/support/metrica/ru/sources/direct-summary.html) / preset `sources_direct_summary`: фильтр `ym:s:<attribution>DirectClickOrder!n` (учтенный клик Директа / yclid), не `AdvEngine`;
- в ответе оставляем только строки с `dimensions[0].id` из выбранных целей (API в разрезе `ym:s:goal` может отдать чужие цели);
- имя цели из `dimensions[0].name`;
- фильтры этапа 2, timezone, attribution — как обычно.

### Ночной съём

Команда `metrika:sync-direct-summary-goals` (расписание `04:30` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.goals_direct_summary`, токен, счётчик, цели, `sync_enabled_at`;
- стратегия: upsert по unique `(project_id, goal_name, month)` в `yandex_metrika_goal_direct_summary`.

## Интеграция Яндекс Метрики (этап 3.5: переходы «Поисковые системы»)

Пятый отчёт этапа 3. Не цели, а переходы (визиты/посетители).

### Ограничение по типу проекта

`visits_search_engines` доступен только для `seo_promotion` (уже через `$seoOnlyVisitReportKeys`).

### Ключи settings

| Ключ | Значения | По умолчанию |
|------|----------|--------------|
| `search_engines_all` | `true` = все ПС (включая будущие); `false` = только `search_engines` | `true` |
| `search_engines` | массив root-ID (`yandex`, `google`, …) | `[]` |
| `visits_metric` | `visits` (Визиты) / `users` (Посетители) | `visits` |

При `search_engines_all=true` массив `search_engines` при сохранении очищается. Legacy-ключ `search_engines_display` (textarea) при чтении мигрируется в ID через [`SearchEnginesDisplayList::migrateDisplayTextToIds()`](src/Domain/YandexMetrika/SearchEnginesDisplayList.php); при новом сохранении не пишется.

### UI

При включённом `visits_search_engines` блок обёрнут рамкой с полями:

1. «Выберите поисковые системы для отчётов» — чекбоксы из API (`loadYandexMetrikaSearchEngines` → `listSearchEngineRootOptions`). Первый пункт — **«Все поисковые системы»** (по умолчанию выбран). Снятие одной ПС при активном «Все» переводит в явный список; если отмечены все видимые — снова включается «Все».
2. «По какому параметру рассчитываем переходы?» — `visits` / `users`.
3. «Проверить работу интеграции» — по шаблону Callibri. Результат: `Количество переходов из отчета Поисковые системы: N`. Livewire: `testYandexMetrikaVisitsSearchEnginesIntegration()`.

Цели для этого отчёта не требуются.

### API

[`YandexMetrikaService::fetchSearchEnginesVisitsStats()`](app/Services/YandexMetrikaService.php):

- группировка: `ym:s:<attribution>SearchEngineRoot` или `ym:s:<attribution>SearchEngineRoot,ym:s:month` (при «Автоматической» → `automaticSearchEngineRoot`);
- метрики: `ym:s:visits` / `ym:s:users`;
- при `search_engines_all=false` — доп. фильтр `ym:s:<attribution>SearchEngineRoot=@'…'` (через [`SearchEnginesDisplayList::buildSearchEngineRootFilter()`](src/Domain/YandexMetrika/SearchEnginesDisplayList.php));
- в БД пишется root-ID (`dimensions[0].id`), label из `name` — для отчётов;
- фильтры этапа 2, timezone, attribution — как обычно.

Список опций для UI: `listSearchEngineRootOptions()` за последние 30 дней, `dimensions=ym:s:<attribution>SearchEngineRoot`, organic + without_robots.

### БД

Колонка `yandex_metrika_search_engines_stats.search_engine` — `VARCHAR(255)`. Goals-синк по-прежнему пишет в `conversions` с ключами `yandex`/`google`/`other`. Visits-синк пишет в `visits` через `upsertSearchEnginesVisits` с root-ID (не затирает `conversions`).

### Ночной съём

Команда `metrika:sync-search-engines-visits` (расписание `05:00` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.visits_search_engines`, токен, счётчик, `sync_enabled_at`;
- период: с начала месяца `sync_enabled_at` по сегодня;
- upsert `visits` по `(project_id, search_engine, month)`.

## Интеграция Яндекс Метрики (этап 3.6: переходы «Поисковые запросы»)

Шестой отчёт этапа 3. Переходы по поисковым фразам с исключением минус-слов.

### Ограничение по типу проекта

`visits_search_queries` доступен только для `seo_promotion` (через `$seoOnlyVisitReportKeys`).

Три отчёта по переходам (`visits_search_engines`, `visits_search_queries`, `visits_geo`) можно включить одновременно (условие «И» с фильтрами этапа 2).

### Ключи settings

| Ключ | Значения | По умолчанию |
|------|----------|--------------|
| `search_queries_minus` | многострочный текст (каждая минус-фраза на своей строке) | `''` |
| `visits_metric` | `visits` / `users` (общий с отчётом «Поисковые системы») | `visits` |

### UI

При включённом `visits_search_queries` блок обёрнут рамкой:

1. «Минус-фразы» — textarea + тултип про брендовые запросы; placeholder «Вакансии» / «Реквизиты».
2. «По какому параметру рассчитываем переходы?» — тот же `visits_metric`.
3. «Проверить работу интеграции» — результат: `Количество переходов из отчета Поисковые запросы: N`. Livewire: `testYandexMetrikaVisitsSearchQueriesIntegration()`.

### API

[`YandexMetrikaService::fetchSearchQueriesVisitsStats()`](app/Services/YandexMetrikaService.php):

- группировка: `ym:s:<attribution>SearchPhrase` (+ `ym:s:month` на несколько месяцев) — как официальный preset `sources_search_phrases` (при «Автоматической» → `ym:s:automaticSearchPhrase`);
- метрики: `ym:s:visits` / `ym:s:users`;
- минус-фразы → AND-фильтр `ym:s:<attribution>SearchPhrase!@'…'` ([`SearchQueriesMinusList`](src/Domain/YandexMetrika/SearchQueriesMinusList.php), исключение по вхождению);
- фильтры этапа 2, timezone, attribution — как обычно.

### БД

Таблица `yandex_metrika_visits_search_queries` (`phrase`, `visits`, `visitors`, `goal_reaches`). Upsert через `upsertSearchQueriesVisits` обновляет только выбранную метрику, не затирая вторую и `goal_reaches`.

### Ночной съём

Команда `metrika:sync-search-queries-visits` (расписание `05:30` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.visits_search_queries`, токен, счётчик, `sync_enabled_at`;
- период: с начала месяца `sync_enabled_at` по сегодня;
- upsert по `(project_id, month, phrase)`.

## Интеграция Яндекс Метрики (этап 3.7: переходы «География»)

Седьмой отчёт этапа 3. Переходы по городам из отчёта [«География»](https://yandex.ru/support/metrica/ru/visitors/geography.html) (API preset `geo_country`).

### Ограничение по типу проекта

`visits_geo` доступен только для `seo_promotion` (через `$seoOnlyVisitReportKeys`).

Все три SEO-отчёта по переходам (`visits_search_engines`, `visits_search_queries`, `visits_geo`) можно включать одновременно (условие «И» с фильтрами этапа 2).

### Ключи settings

| Ключ | Значения | По умолчанию |
|------|----------|--------------|
| `visits_metric` | `visits` / `users` (общий с другими отчётами «Переходы») | `visits` |

### UI

При включённом `visits_geo` блок обёрнут рамкой:

1. «По какому параметру рассчитываем переходы?» — тот же `visits_metric`.
2. «Проверить работу интеграции» — результат: `Количество переходов из отчета География: N`. Livewire: `testYandexMetrikaVisitsGeoIntegration()`.

### API

[`YandexMetrikaService::fetchGeoVisitsStats()`](app/Services/YandexMetrikaService.php):

- группировка: `ym:s:regionCity` (+ `ym:s:month` на несколько месяцев) — лист дерева country → area → city;
- метрики: `ym:s:visits` / `ym:s:users`;
- атрибуция в измерениях не нужна (локация посетителя); параметр `attribution` передаётся как у соседних методов;
- фильтры этапа 2, timezone — как обычно;
- константа измерения: [`GeographyDisplayList::CITY_DIMENSION`](src/Domain/YandexMetrika/GeographyDisplayList.php).

### БД

Таблица `yandex_metrika_visits_geo` (`city`, `visits`, `visitors`, `goal_reaches`). Upsert через `upsertVisitsGeo` обновляет только выбранную метрику, не затирая вторую и `goal_reaches`.

### Ночной съём

Команда `metrika:sync-geo-visits` (расписание `06:00` в [`routes/console.php`](routes/console.php)):

- условия: `is_enabled`, `reports.visits_geo`, токен, счётчик, `sync_enabled_at`;
- период: с начала месяца `sync_enabled_at` по сегодня;
- upsert по `(project_id, month, city)`.

## UI-шаблон: проверка работы интеграции

Эталон: блок «Проверить работу интеграции» в [`callibri-integration-modal-body`](resources/views/components/project-form/callibri-integration-modal-body.blade.php). Копировать в другие модалки интеграций, не изобретать заново.

### Где стоит блок

- **Внутри** `x-panel.scroll-panel` и формы, после основных полей. Не между скроллом и подвалом — иначе ссылка «прилипает» к «Сохранить» / «Отменить».
- При открытии модалки блок **свёрнут** (`testPanelOpen: false`). Видны только ссылка и стрелка вниз.

### Ссылка-аккордеон

- Кнопка `variant="action"` + `wrap` (как «Добавить фильтр…» в Метрике). Не `variant="link"` и не `class="underline"` на всей кнопке: иначе остаются `h-10 px-3.5 inline-flex`, подчёркивание шире текста.
- Стрелка — та же, что у «Вернуться к отчетам»: `<x-icons.arrow-left />`. Иконка смотрит влево, направление задаём поворотом обёртки:
  - свёрнуто: `rotate-270` (вниз);
  - раскрыто: `rotate-90` (вверх);
  - анимация: `transition-transform duration-300`.
- Классы поворота брать **уже используемые** в проекте (`rotate-90`, `rotate-270`). `-rotate-90` может отсутствовать в собранном CSS на staging — стрелка останется влево.
- Клик по строке «текст + стрелка» (обёртка), не по двум обработчикам сразу.
- После раскрытия прокрутить панель в видимую область: `scrollIntoView({ behavior: 'smooth', block: 'end' })` на `x-ref` блока. Скролл идёт внутри `.scrollpanel-content`, не у окна. Вызывать в `$nextTick` + `requestAnimationFrame`, чтобы `x-show` успел показать DOM.

### Дата и кнопка «Проверить»

- Колонка `w-[305px] flex-col gap-3`, как остальные поля модалки.
- Дата на всю ширину. Placeholder: «Выберите дату».
- Кнопка **под** полем, `class="w-full"`, вариант по умолчанию (второстепенная, как «Удалить» у фильтра Метрики), `icon="icons.refresh"`.
- Disabled, пока дата пустая **или** идёт запрос: `x-bind:disabled="!testDate || testLoading"`.
- Тултип «Выберите дату» только без даты. Disabled-кнопка не ловит hover — обёртка с `mouseenter`/`mouseleave` + `x-teleport` + `x-anchor` (как `x-permissions.field-guard`).

### PNG-иконка в кнопке

Файл: `public/images/icons/refresh.png`. Компонент [`icons.refresh`](resources/views/components/icons/refresh.blade.php) — не `<img>`: при hover кнопки текст белый, чёрный PNG останется чёрным. Цвет через `background-color: currentColor` и CSS `mask-image` по PNG.

У SVG-иконок в кнопке (`<x-dynamic-component :component="$icon" />`) на корне нужен `{{ $attributes }}`, иначе `iconClasses` не применяются.

### Чего не делать в blade модалок

Не ставить `@if` внутрь атрибутов `<x-form.checkbox>` / других Blade-компонентов — компилятор даёт `ParseError: unexpected token ":"`. Условия disabled/тултипа — в Alpine (`x-bind:disabled`, `x-show`).
