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
