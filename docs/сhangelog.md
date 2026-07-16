
# Журнал изменений

## Цель
Фиксировать все изменения, багфиксы и новые фичи, чтобы было понятно, как продукт развивается.

## Правила заполнения
- **Формат версии:** `vX.Y.Z`, где:
    - `X` — мажорное обновление (изменения, которые нарушают обратную совместимость (например, удаление старых API, изменение структуры данных).
    - `Y` — минорное обновление (новый функционал, но без ломания совместимости).
    - `Z` — патч (исправления багов, мелкие улучшения).
- Разделение по типу изменений:
    - Новое
    - Исправления
    - Улучшения
    - Устаревшее
- Ссылки на задачи в системе трекера, если они есть (например, https://yt.softorium.pro/issue/SEO-129/Otobrazhenie-arkhivnykh-proektov-v-Mediaplany-KR).
- Дата указывается в формате `YYYY-MM-DD`.
- Свежие версии вверху, старые уходят вниз.

## Когда заполнять?
- Заполняется при каждом изменении кода, влияющем на функционал.
- После завершения работы над задачей (перед отправкой в основную ветку).

## Пример рабочего процесса
1. Разработчик добавил новую фичу.
2. Перед коммитом добавил строку в `Changelog.md`.
3. Создал PR и отправил код на ревью.

## Пример заполнения
## [vX.Y.Z] - YYYY-MM-DD  
### 🚀 Новое  
#### https://yt.softorium.pro/issue/SEO-XX/###
- Добавлена поддержка [фича]  
- Реализована [фича]  


### 🛠 Исправления  
#### https://yt.softorium.pro/issue/SEO-XX/###
- Исправлена ошибка с [описание бага]  
- Оптимизирован алгоритм [функция]  

### 🔧 Улучшения  
#### https://yt.softorium.pro/issue/SEO-XX/###
- Улучшена скорость обработки [модуль]  
- Добавлено логирование в [функция]  

### ⏳ Устаревшие функции  
#### https://yt.softorium.pro/issue/SEO-XX/###
- Удалена поддержка [функция]  


# Releases
## [v0.2.20] - 2026-07-16
### Исправления
- Модалка интеграций: при переключении Callibri ↔ Яндекс.Директ ↔ Search API тело и сайдбар больше не «залипают» на предыдущей интеграции — универсальный remount через `integrationModalBodyRevision` и `wire:key` на обёртках body/sidebar (обход `wire:ignore` в Alpine-теле Директа).

## [v0.2.19] - 2026-07-15
### Исправления
- Яндекс.Директ OAuth: в redirect добавлены scope `login:avatar` и `login:info` — без них `login.yandex.ru/info` не возвращает `default_avatar_id` и `display_name`; аватарка в карточке профиля показывалась fallback-буквой. После деплоя нужен повторный OAuth («Выбрать другую учетную запись»).

## [v0.2.18] - 2026-07-15
### Исправления
- Яндекс.Директ: в карточке OAuth-аккаунта убран дубль `@логин`, когда имя совпадает с логином; URL аватарки больше не кодирует `/` в `default_avatar_id` (раньше 404 → fallback с буквой).
### Улучшения
- Карточка профиля в стиле info-блока Callibri (`bg-blue-50`); кнопка «Выбрать другую учетную запись» для повторного OAuth без выключения синхронизации.

## [v0.2.17] - 2026-07-15
### Улучшения
- Яндекс.Директ: в модалке интеграции показывается карточка Яндекс-аккаунта (имя, логин, аватар), под которым пройден OAuth; данные сохраняются в settings и подгружаются для старых интеграций.

## [v0.2.16] - 2026-07-15
### Исправления
- Яндекс.Директ: при ошибке интеграции ползунок «Синхронизация» и рамка поля «Логин» снова подсвечиваются `#FF7373` (статические CSS-классы `yd-direct-error-*` вместо динамических Tailwind arbitrary в Alpine).

## [v0.2.15] - 2026-07-15
### Исправления
- Яндекс.Директ: ошибки интеграции показываются баннером под заголовком модалки (как в Callibri), а не внутри поля «Логин»; при ошибке ползунок синхронизации и рамка поля «Логин» — `#FF7373`; после успешной загрузки логинов UI возвращается в обычное состояние.
- Кнопка «Удалить интеграцию» снова видна после OAuth даже без выбранного Client-Login (`oauth_token` или `is_enabled`).

## [v0.2.14] - 2026-07-15
### Исправления
- Яндекс.Директ: исправлен base URL API на официальный `…/json/v5/` (было `…/v5/json/` → HTTP 404); добавлена нормализация legacy URL в `YandexDirectService`.
- При недоступности AgencyClients и Clients API больше нет fallback на OAuth-логин родителя — показывается ошибка конфигурации.
- Для staging с реальным OAuth: `YANDEX_DIRECT_USE_SANDBOX=false` и корректные `YANDEX_DIRECT_*_API_URL`.

## [v0.2.13] - 2026-07-15
### Исправления
- Яндекс.Директ: для агентских аккаунтов в select «Логин» загружаются дочерние Client-Login клиентов агентства (`AgencyClients.get` с `Archived=NO` и пагинацией по `LimitedBy`), а не логин представителя агентства.
- При ошибке AgencyClients для Type=AGENCY больше нет fallback на `login.yandex.ru/info`; показывается понятная ошибка.
- Для прямого рекламодателя логин берётся из `Clients.get`, с fallback на OAuth-логин только если Clients API недоступен.

## [v0.2.12] - 2026-07-15
### Исправления
- OAuth Яндекс.Директ: добавлен `force_confirm=yes` — экран выбора аккаунта Яндекса всегда показывается (не silent auth).
- Pending/done в `localStorage` привязаны к одному `cacheDataId` (не применяем stale DONE от прошлой сессии); при старте OAuth сбрасывается `DONE_KEY`.
- Логин Директа (`client_login`) больше не автоподставляется — пользователь выбирает вручную в модалке; redirect-callback тоже не пишет логин из Яндекс ID.

## [v0.2.11] - 2026-07-15
### Исправления
- OAuth Яндекс.Директ: после popup токены и логины гарантированно попадают в Livewire и Alpine (`yandexDirectOAuthRevision`, `yandex-direct-oauth-applied`, серверный `loadYandexDirectLogins`); select логинов переведён на inline Alpine (как Callibri); pending-сессия в `localStorage` + `storage` event.
- OAuth в Cursor/Electron: redirect-поток (`popup=0`) вместо popup; callback открывает модалку (`open_integration=yandex_direct`).

## [v0.2.10] - 2026-07-14
### Исправления
- OAuth Яндекс.Директ: координатор callback перенесён в layout `system-settings` и вызывает Livewire через `Livewire.getByName()` вместо `$wire` в `@script` (не работал в async BroadcastChannel); fallback `cache_data_id` из URL OAuth; убран grace-таймер, который через 30 с выключал toggle до прихода токенов.

## [v0.2.9] - 2026-07-14
### Исправления
- OAuth Яндекс.Директ: токены после callback применяются на уровне страницы клиенто-проекта (`finalizeYandexDirectOAuth`, BroadcastChannel/postMessage в `@script`), а не только внутри модалки; `sessionStorage` + polling при возврате на вкладку; `wire:key` перемонтирует тело модалки после apply (обход stale `wire:ignore`).

## [v0.2.8] - 2026-07-14
### Исправления
- OAuth Яндекс.Директ: после успешного callback токены доходят до модалки даже без `window.opener` — явный UUID `cache_data_id`, `BroadcastChannel`, быстрый polling (~800 мс) с grace 30 с, `visibilitychange` и повторное открытие модалки после apply.

## [v0.2.7] - 2026-07-14
### Исправления
- OAuth Яндекс.Директ: одно нажатие «Синхронизация» больше не открывает 3 вкладки — убраны дублирующие fallback (`window` event, `$wire.$on`, `dispatch`); popup открывается один раз с уникальным именем окна.
- Callback OAuth: `invalid_grant` (код уже использован/истёк) показывает понятную ошибку в popup вместо 500.

## [v0.2.6] - 2026-07-14
### Исправления
- OAuth Яндекс.Директ: убран паттерн `about:blank` (пустая вкладка); popup открывается сразу с OAuth URL после `prepareYandexDirectOAuth`. Fallback URL через `$wire.$on` и `yandex-direct-oauth-prepared.window`; watchdog 3 с закрывает зависший старт.

## [v0.2.5] - 2026-07-13
### Исправления
- OAuth Яндекс.Директ: исправлен старт авторизации в Livewire 4 — вместо несуществующего `$wire.id()` используется `$wire.$id`; popup открывается синхронно (`about:blank`) до async-запроса, чтобы не ловить popup blocker и TypeError после успешного `prepareYandexDirectOAuth`.

## [v0.2.4] - 2026-07-13
### Исправления
- OAuth Яндекс.Директ: при включении «Синхронизация» больше не показывается «Не удалось начать авторизацию» из‑за Livewire morph — модалка с `wire:ignore`, fallback-событие `yandex-direct-oauth-prepared` с URL для открытия popup.

## [v0.2.3] - 2026-07-13
### Исправления
- OAuth Яндекс.Директ: после успешного callback токены надёжно попадают в модалку даже если `window.opener` потерян (cache + polling + `applyYandexDirectOAuthTokens`). Toggle «Синхронизация» больше не остаётся выключенным после «Авторизация завершена».

## [v0.2.2] - 2026-07-10
### Исправления
- OAuth Яндекс.Директ: при пустых `YANDEX_DIRECT_CLIENT_ID` / `CLIENT_SECRET` больше не открывается popup с ошибкой 400 от Яндекса — показывается предупреждение в модалке, `prepareYandexDirectOAuth()` и `YandexDirectOAuthController::redirect()` возвращают понятную ошибку.

### Улучшения
- Унифицирована проверка `platformConfigured` для интеграций (Search API, Яндекс.Директ) через `isSelectedIntegrationPlatformConfigured`.
- `scripts/staging-smoke.sh`: предупреждение, если `YANDEX_DIRECT_CLIENT_ID` не задан.

## [v0.2.1] - 2026-07-10
### Исправления
- Стабилизация авторизации: вход по логину или email, проверка `is_active`, русские сообщения вместо сырого `auth.failed`.
- Feature-тесты переведены с `RefreshDatabase` на `DatabaseTransactions`; guard блокирует `migrate:fresh` без `CASINI_ALLOW_DB_REFRESH` и для БД `casini`, чтобы тесты на staging не затирали пользователей.
- Исправлен редирект после входа: авторизованный пользователь на `/login` и успешный login больше не уходят на главную (`/`), а попадают в `system-settings/dictionaries`; после login — полная перезагрузка страницы (без Livewire navigate).
- Исправлены права `storage/` / `bootstrap/cache/` на staging: кэш Livewire Compiler больше не создаётся от root, устранена ошибка `tempnam()` на `/system-settings/dictionaries`.
- Восстановлены справочники staging (`integrations` и др.) после wipe БД; пустые модалки «Добавить интеграцию» больше не требуют restore из git.

### Улучшения
- Единая политика паролей: в модель передаётся plain-text, cast `hashed` хеширует сам.
- Добавлены скрипты `scripts/staging-fix-permissions.sh`, `scripts/staging-reseed-reference.sh` и расширенный `scripts/staging-smoke.sh` (логин + Livewire cache + integrations count + dictionaries без 500).
- Сидеры `IntegrationSeeder`, `ProductSeeder`, `TooltipSeeder`, `ProductNotificationSeeder` сделаны идемпотентными (`updateOrInsert` по `code`).

## [v0.2.0] - 2026-07-10
### Новое
- Модальное окно интеграции Яндекс.Директ: OAuth во всплывающем окне (popup + `postMessage`), дата включения синхронизации, select логинов из API (`agencyclients` с fallback на логин OAuth-пользователя), кнопка «Удалить интеграцию», «Сохранить» активно только после выбора логина.

### Улучшения
- Унифицированы ключи settings Яндекс.Директ (`client_login`, `oauth_token`, `sync_enabled_at`) с поддержкой legacy camelCase при чтении.

## [v0.1.0] - 2025-03-04
### 🚀 Новое  
#### https://yt.softorium.pro/issue/SEO-144/Obnovlenie-SAO-Razrabotka-spravochnikov-ch1-zadacha-fevralya
- Реализована страница справочников в блоке настроек системы
- Версия системы выложена на тестовый сервер
