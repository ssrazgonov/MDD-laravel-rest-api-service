# MDD Laravel REST API Service

REST API сервиса управления картами членов семьи: управление картами
(primary / dependent), разграничение прав доступа, история статусов и
получение данных о подключённых услугах из внешней системы-провайдера.

Сервис аутентифицирует запросы по **JWT-токену Keycloak** и на основе данных токена
автоматически синхронизирует пользователей, семейные группы и их участников.

---

## Содержание

- [Технологии](#технологии)
- [Архитектура](#архитектура)
- [Модель данных](#модель-данных)
- [Быстрый старт](#быстрый-старт)
- [Переменные окружения](#переменные-окружения)
- [API](#api)
- [Тестирование](#тестирование)
- [Качество кода](#качество-кода)
- [Известные проблемы](#известные-проблемы)

---

## Технологии

| Компонент | Версия / пакет |
|---|---|
| PHP | `^8.2` (сборка и CI — `8.4`) |
| Laravel | `^12.0` |
| Аутентификация | `robsontenorio/laravel-keycloak-guard` + `firebase/php-jwt` (RS256) |
| Документация API | `dedoc/scramble` (OpenAPI) |
| Тесты | Pest + PHPUnit `^11.5` |
| Код-стайл | Laravel Pint, PHP CS Fixer, Rector |
| БД | SQLite (по умолчанию), поддерживается MySQL/PostgreSQL |
| Контейнеризация | Docker, GitLab CI |

> **Важно:** в `composer.json` имя проекта осталось дефолтным (`laravel/laravel`).
> Если сервис публикуется как пакет — стоит переименовать.

---

## Архитектура

Проект построен на **модульной архитектуре**: вместо стандартного Laravel-приложения
вся бизнес-логика сгруппирована в самодостаточные модули в `app/Modules`.

```
app/
├── Base/                     # Базовые абстракции
│   ├── Action.php            # Базовый Action: handleResult() конвертирует ошибку DTO в исключение
│   ├── Dto.php               # Базовый DTO: гидратация, валидация, fromArray/toArray
│   ├── ActionResultDto.php   # Результат действия: status / error / data
│   ├── ErrorDto.php          # Ошибка: error (сообщение) / code
│   ├── ApiResource.php       # JsonResource с общим конвертом ответа
│   ├── Module.php            # Базовый модуль: config, providers, routes, migrations
│   └── ModuleServiceProvider.php
├── Contracts/                # Интерфейсы: Actionable, Dto, Module, ResultDto
├── Exceptions/Handler.php    # Единый JSON-рендер ошибок для API
├── Modules/
│   ├── Auth/                 # Keycloak: middleware, создание пользователя из JWT
│   ├── User/                 # Пользователи, семейные группы, участники, идентификаторы
│   ├── Card/                 # Карты: CRUD, активация, доступы, история статусов
│   └── Provider/             # Интеграция с внешним провайдером услуг
└── Providers/
    ├── AppServiceProvider.php      # Макросы ответа + настройка Scramble
    └── ModuleServiceProvider.php   # Загрузка модулей из config/modules.php
```

### Как загружается модуль

1. `config/modules.php` содержит список классов модулей.
2. `App\Providers\ModuleServiceProvider` инстанцирует каждый модуль и вызывает `boot()`.
3. `App\Base\Module::boot()` последовательно:

   - определяет путь модуля через Reflection (`setupPath`);
   - **config** — сливает `Modules/<Name>/config/<name>.php` в конфиг по ключу `name`
     (поэтому `Provider/config/provider.php` доступен как `config('provider.*')`);
   - **providers** — регистрирует провайдеры модуля;
   - **routes** — подключает `Modules/<Name>/Http/routes.php`;
   - **migrations** — подключает `Modules/<Name>/Database/Migrations`.

> Конфиг модуля мержится как `array_merge(файл, существующий_конфиг)`, то есть
> значения из файла модуля имеют **меньший** приоритет, чем уже загруженный конфиг.

### Слои внутри модуля

```
Http/Controllers  →  Actions  →  Models
       ↓               ↓
   Requests         Dto/ (вход) и Dto/ActionsResults/ (выход)
       ↓
   Resources
```

- **Controller** — только приём запроса, валидация через `FormRequest`, вызов Action,
  возврат `ApiResource` / `response()->json()->api()`.
- **Action** — вся бизнес-логика, один класс = одна операция (`CreateCard`, `ActivateCard`, …).
  Внедряется в контроллер через контейнер как invokable-класс.
- **Dto** — типизированный вход/выход. `Dto::fromArray()` умеет разворачивать
  `camelCase` → `snake_case` и гидрировать enum'ы из строк.
- **ResultDto** — результат операции со `status`, `error`, `data`. `Action::handleResult()`
  бросает `RuntimeException` с кодом из `ErrorDto`, если операция завершилась ошибкой.

### Аутентификация

Запросы проходят через два middleware: `auth:api` (guard с драйвером `keycloak`)
и `keycloak` (`KeycloakUserMiddleware`).

`KeycloakUserMiddleware`:

1. достаёт Bearer-токен;
2. декодирует JWT алгоритмом **RS256** публичным ключом из `KEYCLOAK_REALM_PUBLIC_KEY`;
3. находит или создаёт `User` по claim `sub`;
4. если в токене есть claim `kids` (дети) — `processParentJwt()`, иначе — `processIndividualJwt()`;
5. устанавливает пользователя в guard (`Auth::setUser`).

`KeycloakMemberService` синхронизирует структуру семьи:

- **Родитель** (есть `kids` в токене) → создаёт/находит семейную группу (`type = family`),
  создаёт primary-участника, привязывает к нему `keycloak`-идентификатор
  и синхронизирует детей как `dependent`-участников по **хешу СНИЛС** (`sha256`).
- **Одиночный пользователь** → создаёт персональную группу (`type = individual`).
- Если пользователь уже существует как `dependent` (ребёнок вошёл сам) — аккаунт
  «активируется»: к участнику привязывается `keycloak`-идентификатор.

Публичный ключ можно указать как в PEM-формате, так и одной base64-строкой без заголовков —
middleware сам обернёт его в `-----BEGIN PUBLIC KEY-----`.

---

## Модель данных

```
groups ──1:N──> group_members ──1:N──> cards ──1:N──> card_status_histories
                      │                  │
                      │                  └──1:N──> card_access_controls
                      │                                  ▲
                      ├──1:N──> group_member_identities   │
                      │                                  │
                      └──1:1──> users ───────────────────┘
                                 (group_member_id)
```

| Таблица | Назначение | Ключевые поля |
|---|---|---|
| `users` | Пользователь сервиса | `id` (uuid, = Keycloak `sub`), `group_member_id`, `is_active` |
| `groups` | Семейная или персональная группа | `name`, `type` (`family` / `individual`), `is_active` |
| `group_members` | Участник группы | `group_id`, `type` (`primary` / `dependent`), `name`, `is_active` |
| `group_member_identities` | Идентификаторы участника | `type` (`keycloak` / `snils`), `value`, `is_primary`; unique(`type`,`value`) |
| `cards` | Карта участника | `group_member_id`, `hash` (sha256 PAN), `last_digits`, `status`, `comment` |
| `card_status_histories` | История изменения статуса | `card_id`, `status`, `comment`, `changed_by` |
| `card_access_controls` | Права на карту | `card_id`, `group_member_id`, `access_level` (`view` / `manage`); unique(`card_id`,`group_member_id`) |

Миграции лежат внутри модулей (`app/Modules/*/Database/Migrations`) и подключаются
автоматически при `boot()` модуля. Общие миграции Laravel (cache, jobs) — в `database/migrations`.

**Ключевые бизнес-правила:**

- PAN карты **не хранится** — сохраняется только `sha256`-хеш и последние 4 цифры.
- СНИЛС также хранится в виде `sha256`-хеша.
- У участника может быть **только одна активная карта**: при активации новой карты
  ранее активная автоматически деактивируется.
- **Автоактивация:** если у участника ещё нет активной карты, только что созданная
  карта активируется сразу.
- **Уровни доступа:** `manage` — просмотр, активация, деактивация; `view` — только просмотр.
- При создании карты:
  - для `primary`-участника доступ `manage`;
  - для `dependent`-участника — `view` ему самому и `manage` всем активным
    primary-участникам его группы.

---

## Быстрый старт

### Требования

- PHP 8.2+ с расширениями `openssl`, `pdo`, `mbstring`
- Composer 2
- Node.js 18+ (только для сборки фронтенд-ассетов)
- SQLite (по умолчанию) или другая БД

### Установка

```bash
git clone <url-вашего-репозитория>.git
cd mdd-laravel-rest-api-service

composer install
cp .env.example .env
php artisan key:generate
```

Настройте `.env` — как минимум `KEYCLOAK_REALM_PUBLIC_KEY` (см. раздел ниже).

```bash
# SQLite: создайте файл БД
touch database/database.sqlite

php artisan migrate

# (опционально) ассеты
npm install && npm run build
```

### Запуск

```bash
# Режим разработки: сервер + очередь + логи + Vite
composer run dev

# Или только сервер
php artisan serve
```

Сервис будет доступен по адресу `http://127.0.0.1:8000`.
Проверка живости — `GET /up`.

### Альтернатива: Docker

```bash
docker build \
  --build-arg PHP_VERSION=8.4 \
  --build-arg REGISTRY=docker.io/library \
  -t card-service .

docker run --rm -p 8000:8000 card-service
```

Базовый образ задаётся аргументом `REGISTRY` (по умолчанию `docker.io/library`,
то есть официальный `php:<version>-fpm`). Для закрытого окружения укажите
свой реестр: `--build-arg REGISTRY=registry.example.com/mirror`.

---

## Переменные окружения

Стандартные переменные Laravel (`APP_*`, `DB_*`, `CACHE_*`, `QUEUE_*`, …) — в `.env.example`.
Ниже — специфичные для сервиса.

### Аутентификация (Keycloak)

| Переменная | Описание | Пример |
|---|---|---|
| `AUTH_GUARD` | Guard по умолчанию | `api` |
| `AUTH_GUARD_DRIVER` | Драйвер guard'а `api` | `keycloak` |
| `AUTH_PROVIDER_USERS_MODEL` | Модель пользователя | `App\Modules\User\Models\User` |
| `KEYCLOAK_REALM_PUBLIC_KEY` | Публичный ключ realm'а (RS256) | base64-строка или PEM |
| `KEYCLOAK_LOAD_USER_FROM_DATABASE` | Загружать пользователя из БД | `false` |
| `KEYCLOAK_APPEND_DECODED_TOKEN` | Добавлять декодированный токен | `true` |
| `KEYCLOAK_ALLOWED_RESOURCES` | Разрешённые resource-клиенты | `my-api` |
| `KEYCLOAK_LEEWAY` | Допуск по времени, сек | `60` |

`KEYCLOAK_TOKEN_ENCRYPTION_ALGORITHM` по умолчанию `RS256`;
полный список — в `config/keycloak.php`.

### Интеграция с внешним провайдером услуг

| Переменная | По умолчанию | Описание |
|---|---|---|
| `PROVIDER_URL` | `api.example.com` | Хост внешнего провайдера |
| `PROVIDER_HTTP_SCHEMA` | `https` | Схема запроса |
| `PROVIDER_CLIENT_ID` | — | Client ID |
| `PROVIDER_CLIENT_SECRET` | — | Client Secret |
| `PROVIDER_GRANT_TYPE` | — | Grant type для получения токена |

Клиент провайдера получает access token по OAuth2 и кеширует его на **200 секунд**
в общем кеше (`cache` store) под ключом `provider.access_token.<client_id>`.

---

## API

Базовый префикс — `/api/v1`.

### Формат ответа

Все ответы обёрнуты в единый конверт.

**Успех:**

```json
{
  "status": true,
  "error": null,
  "data": {}
}
```

**Ошибка:**

```json
{
  "status": false,
  "error": {
    "code": 404,
    "message": "Карта не найдена."
  },
  "data": null
}
```

Коды ошибок формируются в `App\Exceptions\Handler`:

| Исключение | HTTP-код |
|---|---|
| `ModelNotFoundException` | 404 |
| `AuthenticationException` | 401 |
| `AuthorizationException` | 403 |
| `ValidationException` | 400 |
| `RuntimeException` / `DomainException` | код исключения |

> `RuntimeException` и `DomainException` возвращают собственный код,
> поэтому бизнес-ошибки создаются как `throw new DomainException('...', 400)`.

### Аутентификация запросов

Все эндпоинты, кроме `GET /up`, требуют заголовок:

```
Authorization: Bearer <keycloak_jwt>
```

### Эндпоинты

#### Карты (`CardModule`)

| Метод | Путь | Описание |
|---|---|---|
| `GET` | `/api/v1/cards` | Список карт текущего пользователя |
| `POST` | `/api/v1/cards` | Создать карту |
| `GET` | `/api/v1/cards/{id}` | Получить карту с историей статусов |
| `POST` | `/api/v1/cards/{id}/activate` | Активировать карту |
| `POST` | `/api/v1/cards/{id}/deactivate` | Деактивировать карту |
| `GET` | `/api/v1/members/{memberId}/cards` | Карты конкретного участника группы |
| `GET` | `/api/v1/members/{memberId}/cards/{cardId}` | Конкретная карта участника |

`GET /cards` и `GET /members/{memberId}/cards` поддерживают фильтр `?status=active|inactive`.

```bash
# Список своих карт
curl -H "Authorization: Bearer $TOKEN" \
  "http://127.0.0.1:8000/api/v1/cards?status=active"

# Создать карту
curl -X POST "http://127.0.0.1:8000/api/v1/cards" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"pan": "2200123456789000", "comment": "Основная карта"}'

# Активировать карту
curl -X POST "http://127.0.0.1:8000/api/v1/cards/1/activate" \
  -H "Authorization: Bearer $TOKEN"
```

**Тело запроса `POST /cards`:**

| Поле | Правила | Обязательное |
|---|---|---|
| `pan` | строка, regex `/^2[0-9]{15,18}$/` (16–19 цифр, начинается с `2`) | да |
| `comment` | строка, максимум 256 символов | нет |
| `memberId` | integer, существует в `group_members` | нет (по умолчанию — свой участник) |

**Объект карты (`CardResource`):**

```json
{
  "id": 1,
  "group_member_id": 1,
  "hash": "9f86d081884c7d659a2feaa0c55ad015...",
  "last_digits": "9000",
  "status": "active",
  "comment": "Основная карта",
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z",
  "status_history": [],
  "access_level": "manage"
}
```

`status_history` присутствует только при загруженной связи,
`access_level` — `view` или `manage`.

> **Обратите внимание:** `GET /cards/{id}` возвращает **403**, а не 404, для
> несуществующей карты — проверка прав `canUserViewCard()` выполняется раньше
> выборки карты.

#### Пользователи и семья (`UserModule`)

| Метод | Путь | Описание |
|---|---|---|
| `GET` | `/api/v1/user/members` | Список активных `dependent`-участников группы пользователя |

```bash
curl -H "Authorization: Bearer $TOKEN" \
  "http://127.0.0.1:8000/api/v1/user/members"
```

#### Интеграция с внешним провайдером услуг (`ProviderModule`)

| Метод | Путь | Описание |
|---|---|---|
| `GET` | `/api/v1/user/services/usage` | Использование услуг из внешней системы |

Query-параметры: `date_from`, `date_to`, `client_id`.

```bash
curl "http://127.0.0.1:8000/api/v1/user/services/usage?date_from=2025-01-01&date_to=2025-01-31"
```

> ⚠️ Этот маршрут объявлен **без** middleware `auth:api` и `keycloak`
> (см. [Известные проблемы](#известные-проблемы)).

### Права доступа

| Действие | Требуемый доступ |
|---|---|
| `GET /cards`, `GET /cards/{id}` | `view` или `manage` |
| `POST /cards/{id}/activate`, `POST /cards/{id}/deactivate` | `manage` |
| `GET /members/{memberId}/cards*` | пользователь должен быть `primary`-участником той же группы |

Нарушение прав возвращает `403` с сообщением:
`У вас нет прав для просмотра/управления этой карты.`

### Документация OpenAPI

Проект использует [Scramble](https://scramble.dedoc.co/) — документация генерируется
из кода. Установите dev-зависимости и откройте:

```
/docs/api
```

Схема безопасности (HTTP Bearer) настраивается в `AppServiceProvider`.

---

## Тестирование

Тесты написаны на **Pest**. В `tests/Pest.php` для всех тестов из `tests/Feature`
подключаются `RefreshDatabase` и `TestHelper`.

```bash
composer test
# или
php artisan test
```

Отдельный файл:

```bash
php artisan test tests/Feature/Modules/Card/Controllers/CardControllerTest.php
```

### Как устроены тесты

- JWT-токены для тестов генерируются `Tests\Helpers\JWTTestHelper` с использованием
  приватного ключа `tests/fixtures/auth/private.pem` (публичный — `tests/fixtures/auth/public.pem`).
- Трейт `Tests\Helpers\TestHelper` создаёт готовые структуры:
  `createParentAndChildrens()` и `createCardForGroupMember()`.
- Фабрики моделей лежат в `database/factories/Modules/**`.

> **Внимание:** в `phpunit.xml` настройки `DB_CONNECTION=sqlite` и
> `DB_DATABASE=:memory:` закомментированы. Для изолированного прогона тестов
> убедитесь, что тестовая БД настроена, или раскомментируйте эти строки.

---

## Качество кода

```bash
# Laravel Pint — код-стайл
./vendor/bin/pint

# PHP CS Fixer
./vendor/bin/php-cs-fixer fix

# Rector — рефакторинг
./vendor/bin/rector process
```

Конфигурация: `rector.php`, `.php-cs-fixer.php`, `.editorconfig`.
Стиль — PSR-12 + правила Laravel (`laravel/pint`).

---

## Известные проблемы

Найденные при анализе кодовой базы несоответствия. Стоит исправить:

1. **Маршрут провайдера без аутентификации.**
   `GET /api/v1/user/services/usage` объявлен вне группы `middleware(['auth:api', 'keycloak'])`,
   в отличие от маршрутов `Card` и `User`. Эндпоинт публично доступен и проксирует
   запросы к внешней системе.

2. **`group_type` вместо `type` в `KeycloakMemberService`.**
   В `app/Modules/User/Services/KeycloakMemberService.php` используется атрибут
   `group_type`, тогда как в миграции `create_groups_table` и в `$fillable` модели
   `Group` поле называется `type`. Обращение `$group->group_type` вернёт `null`,
   а `update(['group_type' => ...])` не сохранится — логика перевода персональной
   группы в семейную **не работает**.

3. **Отсутствующая связь `member` в `Card`.**
   `Card::scopeForUser()` использует `whereHas('member', ...)`, но в модели `Card`
   связь называется `groupMember()`. Вызов скоупа приведёт к исключению.
   Скоуп сейчас нигде не используется — можно удалить или исправить на `groupMember`.

4. **Разные форматы ошибок у DTO и HTTP-слоя.**
   `ErrorDto` описывает поля `error` / `code`, а макрос `apiError()` возвращает
   `code` / `message`. Стоит унифицировать формат.

5. **`Card::$access_level` объявлен как `bool`.**
   В `app/Modules/Card/Models/Card.php` свойство `public bool $access_level`
   фактически хранит `CardAccessLevel` (string enum) и нигде не используется.

6. **`Card::cardAccessControlForCurrentUser()` не защищён от `null`.**
   Если записи в `card_access_controls` нет, `first()` вернёт `null`
   и обращение `->access_level` выбросит ошибку.

7. **Ветка `GetUserMembers` для `memberType !== 'dependent'` нерабочая.**
   Запрос фильтрует по `group_members.user_id`, хотя такого столбца в таблице
   `group_members` нет — связь идёт через `users.group_member_id`.

---

## Лицензия

MIT (см. `composer.json`).
