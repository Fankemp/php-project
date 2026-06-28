# Marketplace Data Aggregator

> Отказоустойчивый Laravel-сервис для автоматической инкрементальной синхронизации данных (Заказы, Продажи, Доходы, Склады) из внешних API маркетплейсов в унифицированную локальную БД.

---

## Содержание

- [Архитектура](#архитектура)
- [Технологический стек](#технологический-стек)
- [Быстрый старт](#быстрый-старт)
- [Переменные окружения](#переменные-окружения)
- [Структура базы данных](#структура-базы-данных)
- [CLI-команды](#cli-команды)
- [Автоматизация (Cron)](#автоматизация-cron)
- [Тестирование](#тестирование)
- [Troubleshooting](#troubleshooting)

---

## Архитектура

Проект построен по принципу **Modular Monolith** с элементами **Clean Architecture**. Каждый слой имеет строго определённую ответственность.

```
app/
├── Console/Commands/
│   ├── CreateEntityCommand.php   # Интерактивная CLI-фабрика сущностей
│   └── SyncAccountsCommand.php   # Оркестратор синхронизации
├── Models/                        # Eloquent-модели с отношениями (Company → Account → Token)
├── Services/
│   ├── ApiClientService.php       # HTTP-клиент с Retry и Rate Limit
│   ├── SyncService.php            # Маппинг и сохранение данных
│   └── Strategies/                # Strategy Pattern для разных типов авторизации
database/
└── migrations/                    # Схема БД: FK, составные уникальные индексы
routes/
└── console.php                    # Регистрация Cron-задач
tests/
└── Feature/SyncServiceTest.php    # Feature-тесты с Http::fake()
```

**Ключевые архитектурные решения:**

| Паттерн | Где применён | Зачем |
|---|---|---|
| **Multi-tenancy** | FK `account_id` + составные `unique`-индексы | Изоляция данных клиентов, защита от дублей |
| **Strategy** | `Services/Strategies/` | Поддержка Bearer / API Key / Login+Password без изменения клиентского кода |
| **Adapter / Data Mapper** | `SyncService` | Трансформация сырых DTO внешних API в доменные модели |
| **Exponential Backoff** | `ApiClientService` | Автоматическое восстановление при HTTP 429 и 5xx |
| **Incremental Sync** | `SyncService` | Запрос только новых данных (по `MAX(date)` из БД), минимизация трафика |
| **withoutOverlapping()** | `routes/console.php` | Гарантия, что два Cron-запуска не пересекутся |

---

## Технологический стек

- **PHP** 8.3+
- **Framework:** Laravel 11.x
- **Database:** MySQL 8.0 (порт **3307** — не конфликтует с локальным MySQL)
- **Infrastructure:** Docker + Docker Compose

---

## Быстрый старт

> **Время до первого запуска: ~2 минуты.** Требуется только Docker.

### 1. Клонируйте репозиторий

```bash
git clone https://github.com/your-username/marketplace-aggregator.git
cd marketplace-aggregator
```

### 2. Настройте окружение

```bash
cp .env.example .env
```

Обратите внимание: `DB_PORT=3307` уже выставлен в `.env.example`, чтобы не конфликтовать с локальным MySQL на порту 3306.

### 3. Поднимите контейнеры

```bash
docker-compose up -d --build
```

Запускается два сервиса: `php` (Laravel + CLI) и `mysql` (MySQL 8.0 на порту 3307). Сервис `scheduler` поднимается автоматически и начинает отслеживать расписание Cron.

### 4. Установите зависимости и подготовьте БД

```bash
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate:fresh
```

### 5. Создайте первую компанию и аккаунт

```bash
docker-compose exec php php artisan manage:create
```

Мастер-команда пошагово проведёт через создание: **Компания → API-сервис → Тип токена → Аккаунт → Токен**.

---

## Переменные окружения

Все параметры находятся в `.env`. Ключевые из них:

| Переменная | Значение по умолчанию | Описание |
|---|---|---|
| `DB_HOST` | `mysql` | Имя сервиса MySQL в Docker-сети |
| `DB_PORT` | `3307` | Нестандартный порт (избегаем конфликта с хостом) |
| `DB_DATABASE` | `aggregator` | Имя базы данных |
| `DB_USERNAME` | `aggregator` | Пользователь БД |
| `DB_PASSWORD` | `secret` | Пароль БД |
| `LOG_CHANNEL` | `stack` | Канал логирования (stack = stderr + daily) |

---

## Структура базы данных

Иерархия данных: **Company → Account → Token → [Orders, Sales, Revenue, Warehouses]**

```
companies
    └── accounts (account.company_id → companies.id)
            └── tokens (token.account_id → accounts.id)
            │       └── api_services (сервис, для которого выдан токен)
            │       └── token_types (Bearer / API Key / Login+Password)
            └── orders        (account_id + external_id = UNIQUE)
            └── sales         (account_id + external_id = UNIQUE)
            └── revenues      (account_id + date = UNIQUE)
            └── warehouses    (account_id + external_id = UNIQUE)
```

Составные `UNIQUE`-индексы по `(account_id, external_id)` на уровне СУБД гарантируют идемпотентность: повторная синхронизация не создаст дублей и не затрёт данные другого аккаунта.

---

## CLI-команды

Всё управление системой — через консоль. Не нужно писать SQL вручную.

### Интерактивный мастер создания сущностей

```bash
docker-compose exec php php artisan manage:create
```

Запускает пошаговый диалог для создания любой сущности:

```
Что создать?
  [1] Компанию
  [2] Аккаунт
  [3] API-сервис
  [4] Тип токена
  [5] Токен для аккаунта
```

### Ручной запуск синхронизации

```bash
docker-compose exec php php artisan sync:accounts
```

Стягивает свежие данные для **всех активных аккаунтов** с подробным выводом в консоль:

```
[2025-06-10 12:00:01] Синхронизация аккаунта #3 (Wildberries / ООО Ромашка)
[2025-06-10 12:00:01]   → Последняя дата в БД: 2025-06-08
[2025-06-10 12:00:03]   ✓ Загружено 142 заказа
[2025-06-10 12:00:04]   ✓ Загружено 58 позиций склада
[2025-06-10 12:00:04]   Аккаунт #3 синхронизирован за 3.1 сек
```

### Синхронизация конкретного аккаунта

```bash
docker-compose exec php php artisan sync:accounts --account=3
```

---

## Автоматизация (Cron)

В `docker-compose.yml` предусмотрен выделенный сервис `scheduler`. Он работает как демон и не требует настройки системного crontab.

**Расписание:** синхронизация запускается автоматически **дважды в сутки** — в **01:00** и **13:00**.

Защита от параллельного запуска (`withoutOverlapping()`) гарантирует, что если предыдущая синхронизация ещё не завершилась, новая не запустится поверх неё.

Просмотр логов планировщика в реальном времени:

```bash
docker logs app_scheduler -f
```

---

## Тестирование

Проект покрыт Feature-тестами. HTTP-запросы к внешним API перехватываются через `Http::fake()` — тесты работают без реального интернета и без API-ключей.

```bash
docker-compose exec php php artisan test
```

Что тестируется:
- Корректность маппинга ответов API в модели БД
- Поведение при HTTP 429 (проверяем, что Retry срабатывает)
- Идемпотентность: повторный запуск синхронизации не создаёт дублей
- Изоляция аккаунтов: данные одного аккаунта не перезаписывают данные другого

---

## Troubleshooting

**Ошибка `Connection refused` при подключении к БД**

MySQL стартует несколько секунд. Если `migrate:fresh` упал сразу после `up -d`, подождите 10–15 секунд и повторите.

```bash
docker-compose ps          # убедитесь, что mysql в состоянии "healthy"
docker-compose exec php php artisan migrate:fresh
```

**Порт 3307 занят на хост-машине**

Измените `DB_PORT` и маппинг в `docker-compose.yml`:

```yaml
ports:
  - "3308:3306"   # меняем только хостовую часть
```

И обновите `.env`: `DB_PORT=3308`.

**Синхронизация зависает или падает с таймаутом**

Проверьте логи PHP-контейнера:

```bash
docker logs app_php -f
```

Если проблема в Rate Limit (429 от API), сервис автоматически выполнит до 3 попыток с экспоненциальной задержкой. При повторяющихся ошибках проверьте актуальность токена через `manage:create`.