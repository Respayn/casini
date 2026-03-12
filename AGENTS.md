# Casini — Система управления проектами и отчётами

## Обзор проекта

**Casini** — веб-приложение на базе Laravel 12 для управления проектами, клиентами, отчётами и интеграциями с внешними сервисами (Яндекс.Директ, Яндекс.Метрика, Callibri, Мегаплан). Система предназначена для SEO-агентств и управления маркетинговыми проектами.

### Основные технологии

| Категория | Технология |
|-----------|------------|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Livewire 4, Alpine.js, Tailwind CSS 4 |
| Сборка | Vite 6 |
| База данных | MariaDB 10 |
| Веб-сокеты | Laravel Reverb |
| API авторизация | Laravel Sanctum |
| Ролевая модель | Spatie Permission |
| Документы | PhpOffice/PhpWord |
| Контейнеризация | Docker, Docker Compose |

### Архитектура

Проект использует гибридную архитектуру, сочетающую классическую структуру Laravel с модульным DDD-подходом:

```
casini/
├── app/                    # Laravel-приложение
│   ├── Http/               # Контроллеры, Middleware, Requests
│   ├── Models/             # Eloquent-модели
│   ├── Services/           # Сервисы бизнес-логики
│   ├── Repositories/       # Репозитории
│   ├── Livewire/           # Livewire-компоненты
│   └── ...
├── src/                    # Доменная логика (Clean Architecture + CQRS)
│   ├── Domain/             # Доменные сущности, Value Objects, интерфейсы
│   ├── Application/        # Commands, Queries, Handlers, DTO
│   ├── Infrastructure/     # Реализации репозиториев, сервисов
│   └── Planning/           # Подмодуль планирования проектов
├── resources/              # Шаблоны Blade, стили, JavaScript
├── routes/                 # Маршруты (web, api, channels)
├── tests/                  # Unit и Feature тесты
├── docs/                   # Документация проекта
└── docker/                 # Docker-конфигурация
```

#### Принципы Clean Architecture в src/

- **Domain** — чистая бизнес-логика, не зависит от фреймворка
- **Application** — оркестрация use-case по паттерну CQRS
- **Infrastructure** — реализации интерфейсов, работа с БД/API

Направление зависимостей:
```
Presentation → Application → Domain
                    ↑
             Infrastructure
```

---

## Системные требования

- PHP >= 8.2 с расширениями: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, XML
- Composer 2
- Node.js 23
- MariaDB 10 / MySQL
- Docker и Docker Compose (опционально)

---

## Сборка и запуск

### Локальная разработка

```bash
# 1. Клонирование репозитория
git clone git@code.softorium.pro:siteactive/casini.git
cd casini

# 2. Установка зависимостей
composer install
npm install

# 3. Настройка окружения
cp .env.example .env
php artisan key:generate

# 4. Миграции и сиды
php artisan migrate
php artisan db:seed

# 5. Запуск (сервер + очередь + Vite)
composer run dev
```

### Docker

```bash
# Запуск контейнеров
docker-compose -f docker/docker-compose.yml up -d

# Установка зависимостей
docker exec -it casini composer install
docker exec -it casini_nodejs npm install

# Настройка
cp .env.example .env
docker exec -it casini php artisan key:generate

# Миграции
docker exec -it casini php artisan migrate
docker exec -it casini php artisan db:seed

# Тестовые данные (опционально)
docker exec -it casini php artisan db:seed --class=StageSeeder
```

**Доступные адреса:**
- Приложение: http://localhost:8080
- PhpMyAdmin: http://localhost:8081
- Vite HMR: http://localhost:5173

### Авторизация по умолчанию

- Логин: `admin`
- Пароль: `123123`

---

## Команды разработки

| Команда | Описание |
|---------|----------|
| `composer run dev` | Запуск сервера, очереди и Vite параллельно |
| `php artisan serve` | Запуск development-сервера |
| `php artisan queue:listen` | Запуск обработчика очередей |
| `npm run dev` | Запуск Vite в режиме разработки |
| `npm run build` | Сборка фронтенда для production |
| `php artisan migrate` | Применение миграций |
| `php artisan db:seed` | Заполнение БД начальными данными |
| `php artisan test` | Запуск тестов PHPUnit |
| `php artisan pint` | Форматирование кода (Laravel Pint) |

### Тестирование

```bash
# Запуск всех тестов
php artisan test

# Только Unit-тесты
php artisan test --testsuite=Unit

# Только Feature-тесты
php artisan test --testsuite=Feature
```

---

## Стиль кодирования

### Общие правила

- Отступы: 4 пробела
- Конец строки: LF
- Кодировка: UTF-8
- Файл должен заканчиваться пустой строкой

### PHP

- Следовать стандартам PSR-12
- Использовать типизацию аргументов и возвращаемых значений
- DTO для передачи данных между слоями (Spatie Laravel Data)
- Бизнес-логику размещать в `src/Domain/`
- Eloquent-модели в `app/Models/` — только для связей и атрибутов, без бизнес-логики

### CQRS в src/Application/

**Команды (изменение данных):**
```
src/Application/{Feature}/{Operation}/
├── CreateEntityCommand.php
└── CreateEntityCommandHandler.php
```

**Запросы (чтение данных):**
```
src/Application/{Feature}/{Operation}/
├── GetEntityListQuery.php
└── GetEntityListQueryHandler.php
```

### Livewire

- Компоненты: `app/Livewire/`
- Представления: `resources/views/livewire/`
- Использовать многокомпонентный формат (mfc) по умолчанию

### Frontend

- Tailwind CSS 4 для стилей
- Alpine.js для интерактивности
- Vite для сборки

---

## Интеграции

| Сервис | Назначение | Конфигурация |
|--------|------------|--------------|
| Яндекс.Директ | Управление рекламными кампаниями | `YANDEX_DIRECT_*` в .env |
| Яндекс.Метрика | Аналитика | `YANDEX_METRIKA_*` в .env |
| Callibri | Лиды и звонки | `CALLIBRI_API_URL` в .env |
| Мегаплан | CRM | `App\Services\Megaplan\*` |
| Яндекс.SmartCaptcha | Защита форм | `YANDEX_SMARTCAPTCHA_*` в .env |

---

## Структура ключевых директорий

### app/Services/

Бизнес-логика Laravel-части приложения (планируется перенос в src):
- `ClientService` — управление клиентами
- `ProjectService` — управление проектами
- `UserService` — пользователи
- `YandexDirectService` / `YandexMetrikaService` — интеграции
- `ReportService` — генерация отчётов

### app/Livewire/

Интерактивные компоненты интерфейса:
- `SystemSettings/` — настройки системы
- `Forms/` — формы создания/редактирования
- `Channels/` — управление каналами

### src/Domain/

Доменные сущности без зависимостей от фреймворка:
- `Projects/`, `Clients/`, `Reports/`, `Templates/`
- `ValueObjects/` — Quarter, Kpi, ProjectType и др.

### src/Application/

Use-case по паттерну CQRS:
- `Reports/` — генерация и скачивание отчётов
- `Templates/` — управление шаблонами
- `ColumnSettings/` — настройки колонок

---

## Документация

Документация проекта находится в `docs/`:

- `architecture.md` — архитектура и гайдлайны по расположению файлов
- `tech-doc.md` — техническая документация API и алгоритмов
- `glossary.md` — глоссарий терминов
- `changelog.md` — журнал изменений

---

## Добавление новой функциональности

### Чек-лист размещения кода

1. **Зависит от фреймворка/UI/БД?** → `src/Infrastructure/`
2. **Чистая бизнес-логика?** → `src/Domain/`
3. **Оркестрация use-case?** → `src/Application/`

### Пример: новый модуль Tasks

```
src/
├── Domain/Tasks/
│   ├── Task.php
│   ├── TaskRepositoryInterface.php
│   └── TaskStatus.php
├── Application/Tasks/
│   ├── Create/
│   │   ├── CreateTaskCommand.php
│   │   └── CreateTaskCommandHandler.php
│   └── GetList/
│       ├── GetTasksListQuery.php
│       └── GetTasksListQueryHandler.php
└── Infrastructure/Persistence/
    └── TaskRepository.php

app/
├── Models/Task.php
└── Livewire/Forms/TaskForm.php
```

---

## Полезные ссылки

- [Laravel Documentation](https://laravel.com/docs/12.x)
- [Livewire v4](https://livewire.laravel.com/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)
