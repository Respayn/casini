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

**Свой профиль:** маршрут `system-settings.users.edit` для `user.id === auth()->id()` доступен любому авторизованному (middleware `EnsureCanAccessUserEdit`). Список и создание пользователей — только с `* system settings users`. Поля логин / статус / роль / ставка / Мегаплан редактируются только при `edit|full system settings users`; без этого права — disabled в UI и отсекаются в `UserProfileAccess::mergeSavePayload`.

## Клиенты и клиенто-проекты (доступ)

Маршруты списка и формы проекта: middleware `ClientsAndProjectsPermissions` — любое из read|edit|full для родителя `clients and projects`, `… self`, `… all`.

- Список фильтруется `ClientListVisibilityFilter` (self: менеджер клиента / specialist проекта; all: всё).
- Создание и сохранение клиента/проекта — `ensureUserCanEdit` (edit|full self|all).
- Открытие существующего проекта — `ClientProjectAccessPolicy` (all или self с привязкой).

## Тестирование

- **Unit-тесты** для доменной логики в `tests/Unit/Domain/`
- **Feature-тесты** для прикладной логики в `tests/Feature/Application/`

## Интеграция Callibri

### Часовой пояс и подсчёт обращений

Callibri API (`site_get_statistics`) возвращает поле `date` в **UTC**. Кабинет Callibri (ЕЖЛ) показывает время в **часовом поясе проекта**. Параметра timezone в запросе API нет.

**Источник TZ в Casini:** `agency_settings.time_zone` (настройки агентства). Должен совпадать с «Часовым поясом» проекта в Callibri.

**Алгоритм** (`CallibriService`):

1. Запрос статистики за `(день N − 1) … день N` (или расширение начала периода на −1 день для диапазонов).
2. Парсинг `date` каждого обращения как UTC.
3. Конвертация в TZ агентства и фильтрация по локальной календарной дате.
4. Применение пользовательских фильтров (UTM, типы обращений, `is_lid` для «Только первичные»).

**Поля API:**

- `is_lid` — первое обращение абонента.
- `call_status` — причина неответа на звонок; пусто у отвеченных звонков.
- **Интеграционные тесты** в `tests/Feature/Integration/`