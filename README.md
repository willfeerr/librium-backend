# BookMarket API

Backend RESTful em PHP 8.3 + Laravel 12 para compra, venda, troca, chat e avaliacao de livros entre usuarios.

## Stack

- PHP 8.3
- Laravel 12
- Laravel Sanctum
- Laravel Reverb
- PostgreSQL ou MySQL
- Redis, queues e cache
- Stripe + Stripe Connect
- Scalar/OpenAPI
- PHPUnit
- Docker

## Subir com Docker

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

A API fica em `http://localhost:8000/api`.

A documentacao Scalar fica em:

```text
http://localhost:8000/api/docs
```

OpenAPI JSON:

```text
http://localhost:8000/api/docs/openapi.json
```

## Dev Container

O projeto inclui `.devcontainer/` para VS Code/Cursor.

No editor, rode:

```text
Dev Containers: Reopen in Container
```

O container sobe apenas `app` e `postgres` para evitar pulls desnecessarios durante o bootstrap. Dentro do Dev Container, a API roda com `php artisan serve` em `localhost:8000`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` e `BROADCAST_CONNECTION=log`.

O `app` executa `.devcontainer/container-start.sh` como processo principal. Esse script instala dependencias quando necessario, cria `.env`, gera `APP_KEY`, cria o storage link, roda migrations e inicia o Laravel em `0.0.0.0:8000`.

As portas sao publicadas diretamente pelo Docker Compose, sem depender do forward automatico do editor:

- API: `localhost:8000`
- PostgreSQL: `localhost:5433`

Se a porta `8000` estiver ocupada, copie `.devcontainer/.env.example` para `.devcontainer/.env` e ajuste:

```bash
BOOKMARKET_API_PORT=8001
```

URLs locais:

```text
http://localhost:8000/api/health
http://localhost:8000/api/docs
```

Se estiver usando Remote SSH e o forward/tunnel do editor nao aparecer, diagnostique em ordem:

Dentro do Dev Container:

```bash
sh .devcontainer/check-network.sh
```

No host remoto, fora do container:

```bash
sh .devcontainer/check-network.sh
```

Se o host remoto responder, mas o navegador local nao abrir, rode no seu computador:

```bash
ssh -N -L 8000:127.0.0.1:8000 usuario@host-remoto
```

Depois abra:

```text
http://localhost:8000/api/docs
```

Para popular dados de exemplo:

```bash
php artisan db:seed --force
```

## Deploy no Coolify

Use `docker-compose.coolify.yml` no Coolify. O `docker-compose.yml` principal e para desenvolvimento local e usa bind mounts (`.:/var/www/html` e config do Nginx), que podem falhar em deploy com erro de mount.

No Coolify, configure:

- Compose file: `docker-compose.coolify.yml`
- Service exposto: `nginx`
- Porta HTTP: `80`
- Dominio: `https://perrin.skrbe.com`

Variaveis obrigatorias/recomendadas:

Todas as variaveis abaixo tambem estao expostas no `docker-compose.coolify.yml` com defaults (`${VAR:-default}`), para preencher direto no painel do Coolify. Use `.env.coolify.example` como base.

```env
APP_NAME=BookMarket
APP_ENV=production
APP_KEY=base64:gere-uma-chave-segura
APP_DEBUG=false
APP_URL=https://perrin.skrbe.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=bookmarket
DB_USERNAME=bookmarket
DB_PASSWORD=troque-esta-senha

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
BROADCAST_CONNECTION=reverb
REDIS_HOST=redis

SANCTUM_STATEFUL_DOMAINS=perrin.skrbe.com
CORS_ALLOWED_ORIGINS=https://perrin.skrbe.com
SESSION_SECURE_COOKIE=true
```

Gere `APP_KEY` com:

```bash
printf 'base64:' && openssl rand -base64 32
```

Depois do deploy, valide:

```text
https://perrin.skrbe.com/api/health
https://perrin.skrbe.com/api/docs
```

## Testes

```bash
docker compose exec app php artisan test
```

## Endpoints principais

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`
- `POST /api/auth/reset-password`
- `GET /api/auth/me`
- `GET /api/books?search=clean&author=robert&price_min=10&price_max=50`
- `POST /api/books`
- `POST /api/book-reviews`
- `POST /api/seller-reviews`
- `POST /api/listings`
- `POST /api/orders`
- `POST /api/exchanges`
- `GET /api/conversations`
- `POST /api/conversations/{conversation}/messages`
- `GET /api/favorites`
- `GET /api/cart`
- `POST /api/payments/checkout`
- `POST /api/stripe/connect`
- `GET /api/notifications`
- `GET /api/admin/dashboard`
- `POST /api/uploads`

## Autenticacao

Use o token retornado por `/api/auth/register` ou `/api/auth/login`:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Paginacao

As listagens retornam:

```json
{
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 0
  }
}
```

## Pagamentos

Sem `STRIPE_SECRET`, os endpoints retornam payload `mock` para desenvolvimento local. Com `STRIPE_SECRET` e `STRIPE_WEBHOOK_SECRET`, a API cria sessoes reais de Checkout e processa webhook `checkout.session.completed`.
