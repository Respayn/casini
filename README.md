# Casini

## Системные требования
- PHP >= 8.2 с расширениями
    - Ctype PHP Extension
    - cURL PHP Extension
    - DOM PHP Extension
    - Fileinfo PHP Extension
    - Filter PHP Extension
    - Hash PHP Extension
    - Mbstring PHP Extension
    - OpenSSL PHP Extension
    - PCRE PHP Extension
    - PDO PHP Extension
    - Session PHP Extension
    - Tokenizer PHP Extension
    - XML PHP Extension
- Composer 2
- Node 23


## Развертывание
1. Склонировать репозиторий проекта
```sh
git clone git@code.softorium.pro:siteactive/casini.git
```

2. Установить зависимости
```sh
composer install
npm install
```

3. Создать и настроить файл окружения .env
```sh
cp .env.example .env
php artisan key:generate
```

4. Создание БД
```sh
php artisan migrate
php artisan db:seed
```

5. Запуск
```
composer run dev
```

## Запуск через Docker

Для запуска проекта через Docker выполните следующие шаги:

1. Убедитесь, что у вас установлен Docker и Docker Compose

2. Запустите контейнеры:
```sh
docker-compose -f docker/docker-compose.yml up -d
```

3. Установите зависимости PHP:
```sh
docker exec -it casini composer install
```

4. Установите зависимости Node.js:
```sh
docker exec -it casini_nodejs npm install
```

5. Создайте и настройте файл окружения .env:
```sh
cp .env.example .env
docker exec -it casini php artisan key:generate
```

6. Выполните миграции и сиды:
```sh
docker exec -it casini php artisan migrate
docker exec -it casini php artisan db:seed
```

7. Приложение будет доступно по адресу:
- Основное приложение: http://localhost:8080
- PhpMyAdmin: http://localhost:8081
- Фронтенд (Vite): http://localhost:5173

## Авторизация в системе

По-умолчанию доступен пользователь admin, пароль: 123123
