# slots.tube — для AI-агентов

Перед любой работой прочитай `docs/README.md` (обзор, карта репо, ссылки на остальные документы).
Ключевое: деплой — `git push origin main` + `deploy` на сервере (docs/deploy.md); разворот с нуля — docs/from-scratch.md;
секретов в репо нет (docs/env.md); на сервере artisan/git запускать только `sudo -u deploy …`.
Фронт публичного сайта — статические `public/css|js` без сборки (меняешь — обнови `?v=` в шаблонах).
Переводимые поля — JSON-колонки (spatie/laravel-translatable): сортировать через `TranslatableSort`.

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
