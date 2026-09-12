# Laravel API + Vue 3 SPA

Скелет для новых проектов: бэкенд — Laravel API, фронт — отдельное Vue 3 SPA (Vite, Composition API, Vuetify, SCSS).

## Стек

| Часть | Что |
| --- | --- |
| Backend | Laravel 13, PHP 8.4, JSON API |
| Frontend | Vue 3, Vite, Vue Router, Vuetify 3, SCSS, Axios |
| БД | PostgreSQL 16 |
| Очереди / кэш | Redis 7 (`QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`) |
| HTTP | nginx + php-fpm |

Redis сразу в стеке: очереди и кэш/троттлинг (анти-бан парсера).

Парсер Яндекс.Карт (Playwright) лежит в `backend/parser`. Laravel вызывает его через `YandexMapsParser`, токены Яндекса не подделываются — их выставляет Chromium.

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
