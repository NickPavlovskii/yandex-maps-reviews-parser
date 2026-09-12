# Парсер отзывов Яндекс.Карт

Laravel API + Vue 3 SPA. По ссылке на карточку организации собирает рейтинг и отзывы, кладёт их в свою БД и отдаёт пагинацию уже без Яндекса.

## Стек

| Часть | Что |
| --- | --- |
| Backend | Laravel 13, PHP 8.4, JSON API |
| Frontend | Vue 3, Vite, Vue Router, Vuetify 3, SCSS, Axios |
| БД | PostgreSQL 16 |
| Очереди / кэш | Redis 7 (`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`) |
| HTTP | nginx + php-fpm |

Redis сразу в стеке: очереди и кэш/троттлинг (анти-бан парсера).

## Как устроен парсинг

Боевой путь пользователя — только очередь. HTTP-запрос не открывает Яндекс.

```text
POST /api/organizations { url }
        ↓
организация в БД (pending) + Job в Redis
        ↓
сразу 202

воркер
  Playwright открывает исходную ссылку (куки/сессия)
        ↓
  параллельно GET /maps/org/{id}/reviews/?page=N  (до 5 сразу)
        ↓
  отзывы из встроенного JSON в HTML
        ↓
  если страниц не хватило — скролл как fallback
        ↓
  upsert в organizations + reviews

GET /api/organizations/{id}
GET /api/organizations/{id}/reviews?page=1&per_page=50
        ↑ только своя БД
```

`POST /api/yandex/parse` — старое имя того же `store()`: тоже 202 и очередь, парсера в запросе нет.

`POST /api/organizations/parse` — служебный sync для Swagger и локальной отладки. Парсит внутри HTTP-запроса и сразу возвращает то, что записалось в БД. В production выключен (`PARSER_SYNC_ENABLED=false`, по умолчанию выкл. если `APP_ENV=production`).

HTML-пагинация проверена вживую на `https://yandex.ru/maps/org/73966097892/reviews/`: в `<script type="application/json">` есть `reviewId` и `ratingData`, `?page=2` отдаёт другую порцию, чем `?page=1` (50 + 50 + 50 + 32 = 182, без дублей), весь сбор занял ~4 с. Подпись `s` у внутреннего `fetchReviews` при смене `page` отвечает 400 — поэтому основной быстрый путь именно HTML-карточка, а не XHR.

## Что принимают ссылки

Нужна карточка организации, из которой без сети извлекается `businessId`: `/org/123`, slug + id, `oid=`, `poi[uri]`, `businessId=`.

Короткие «Поделиться» вида `https://yandex.ru/maps/-/CHHD5XYs` сейчас отклоняются: в них нет oid, только код редиректа. Резолвить HEAD/GET до финального URL на этапе FormRequest не стали — это уже сеть в валидации. Если доделывать: отдельный шаг в Job/сервисе (не в FormRequest), один переход по короткой ссылке, затем обычный `businessId`.

```bash
cd backend/parser
npm install
npx playwright install chromium
npm run parse -- "https://yandex.ru/maps/org/156355253662"
```

## Запуск

```bash
docker compose up --build
```

| Сервис | URL |
| --- | --- |
| SPA | http://localhost:5173 |
| API | http://localhost:8080/api/health |
| Swagger (sync debug) | http://localhost:8080/api/documentation |
| Laravel health | http://localhost:8080/up |

Сидер создаёт пользователя:

- email: `admin@example.com`
- пароль: `password`

Повторно накатить миграции и сидер:

```bash
docker compose exec php php artisan migrate --seed
```

Очереди обрабатывает контейнер `queue` (`php artisan queue:work redis`).

## Структура

```text
backend/                 Laravel API-only
  app/Http/Controllers/Api
  routes/api.php
frontend/                Vue 3 SPA
  src/api/               axios + модули API
  src/components/global  общие UI-компоненты
  src/composables/
  src/layouts/           TheDefault, TheMain
  src/modules/           страницы по доменам
  src/plugins/           Vuetify
  src/scss/
  src/store/
  src/types/
  src/views/
docker/                  php-fpm, nginx
docker-compose.yml
```

Новый экран: папка в `frontend/src/modules/<domain>/`, маршрут в `frontend/src/router.ts`.  
Новый API: контроллер в `backend/app/Http/Controllers/Api`, клиент в `frontend/src/api`.

Общие UI-компоненты — в `frontend/src/components/global`. Регистрируются плагином (как в ИС «Отчёты»): добавить `.vue`, строку в `manifest.ts` и тип в `types/global-components.d.ts`. Дальше тег (`app-page-header`, `app-info-card`, `app-button`) можно ставить в шаблон без `import`.
