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

## Авторизация в системе

По-умолчанию доступен пользователь admin, пароль: 123123
