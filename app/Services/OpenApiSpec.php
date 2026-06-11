<?php

namespace App\Services;

class OpenApiSpec
{
    public static function make(): array
    {
        $json = ['application/json' => ['schema' => ['$ref' => '#/components/schemas/JsonEnvelope']]];
        $created = ['201' => ['description' => 'Criado', 'content' => $json]];
        $ok = ['200' => ['description' => 'OK', 'content' => $json]];
        $deleted = ['204' => ['description' => 'Sem conteudo']];
        $protected = [['bearerAuth' => []]];
        $serverUrl = app()->runningInConsole()
            ? rtrim((string) config('app.url'), '/').'/api'
            : request()->getSchemeAndHttpHost().'/api';

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'BookMarket API',
                'version' => '1.0.0',
                'description' => 'API RESTful para compra, venda, troca, chat e avaliacao de livros.',
            ],
            'servers' => [
                ['url' => $serverUrl],
            ],
            'tags' => [
                ['name' => 'System'],
                ['name' => 'Auth'],
                ['name' => 'Users'],
                ['name' => 'Books'],
                ['name' => 'Categories'],
                ['name' => 'Reviews'],
                ['name' => 'Marketplace'],
                ['name' => 'Orders'],
                ['name' => 'Exchanges'],
                ['name' => 'Chat'],
                ['name' => 'Favorites'],
                ['name' => 'Cart'],
                ['name' => 'Payments'],
                ['name' => 'Notifications'],
                ['name' => 'Admin'],
                ['name' => 'Uploads'],
            ],
            'paths' => [
                '/health' => [
                    'get' => ['tags' => ['System'], 'summary' => 'Status da API', 'responses' => $ok],
                ],
                '/auth/register' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Cadastrar usuario', 'responses' => $created],
                ],
                '/auth/login' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Autenticar usuario', 'responses' => $ok],
                ],
                '/auth/logout' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Encerrar token atual', 'security' => $protected, 'responses' => $ok],
                ],
                '/auth/refresh' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Renovar token Sanctum', 'security' => $protected, 'responses' => $ok],
                ],
                '/auth/forgot-password' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Enviar link de recuperacao', 'responses' => $ok],
                ],
                '/auth/reset-password' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Redefinir senha', 'responses' => $ok],
                ],
                '/auth/reset-password/{token}' => [
                    'get' => ['tags' => ['Auth'], 'summary' => 'Payload para tela de reset de senha', 'responses' => $ok],
                ],
                '/auth/verify-email/{id}/{hash}' => [
                    'get' => ['tags' => ['Auth'], 'summary' => 'Verificar email por link assinado', 'responses' => $ok],
                ],
                '/auth/email/verification-notification' => [
                    'post' => ['tags' => ['Auth'], 'summary' => 'Reenviar verificacao de email', 'security' => $protected, 'responses' => $ok],
                ],
                '/auth/me' => [
                    'get' => ['tags' => ['Auth'], 'summary' => 'Usuario autenticado', 'security' => $protected, 'responses' => $ok],
                ],
                '/users' => [
                    'get' => ['tags' => ['Users'], 'summary' => 'Listar usuarios', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Users'], 'summary' => 'Criar usuario como admin', 'security' => $protected, 'responses' => $created],
                ],
                '/users/{id}' => [
                    'get' => ['tags' => ['Users'], 'summary' => 'Detalhar usuario', 'security' => $protected, 'responses' => $ok],
                    'put' => ['tags' => ['Users'], 'summary' => 'Atualizar usuario', 'security' => $protected, 'responses' => $ok],
                    'delete' => ['tags' => ['Users'], 'summary' => 'Excluir usuario', 'security' => $protected, 'responses' => $deleted],
                ],
                '/books' => [
                    'get' => ['tags' => ['Books'], 'summary' => 'Listar livros com filtros', 'parameters' => self::bookFilters(), 'responses' => $ok],
                    'post' => ['tags' => ['Books'], 'summary' => 'Cadastrar livro', 'security' => $protected, 'responses' => $created],
                ],
                '/books/{id}' => [
                    'get' => ['tags' => ['Books'], 'summary' => 'Detalhar livro', 'responses' => $ok],
                    'put' => ['tags' => ['Books'], 'summary' => 'Atualizar livro', 'security' => $protected, 'responses' => $ok],
                    'delete' => ['tags' => ['Books'], 'summary' => 'Excluir livro', 'security' => $protected, 'responses' => $deleted],
                ],
                '/categories' => [
                    'get' => ['tags' => ['Categories'], 'summary' => 'Listar categorias', 'responses' => $ok],
                    'post' => ['tags' => ['Categories'], 'summary' => 'Criar categoria', 'security' => $protected, 'responses' => $created],
                ],
                '/categories/{id}' => [
                    'get' => ['tags' => ['Categories'], 'summary' => 'Detalhar categoria', 'responses' => $ok],
                    'put' => ['tags' => ['Categories'], 'summary' => 'Atualizar categoria', 'security' => $protected, 'responses' => $ok],
                    'delete' => ['tags' => ['Categories'], 'summary' => 'Excluir categoria', 'security' => $protected, 'responses' => $deleted],
                ],
                '/book-reviews' => [
                    'post' => ['tags' => ['Reviews'], 'summary' => 'Avaliar livro', 'security' => $protected, 'responses' => $created],
                ],
                '/books/{id}/reviews' => [
                    'get' => ['tags' => ['Reviews'], 'summary' => 'Listar avaliacoes de um livro', 'responses' => $ok],
                ],
                '/book-reviews/{id}' => [
                    'delete' => ['tags' => ['Reviews'], 'summary' => 'Excluir avaliacao de livro', 'security' => $protected, 'responses' => $deleted],
                ],
                '/seller-reviews' => [
                    'post' => ['tags' => ['Reviews'], 'summary' => 'Avaliar vendedor', 'security' => $protected, 'responses' => $created],
                ],
                '/users/{id}/reviews' => [
                    'get' => ['tags' => ['Reviews'], 'summary' => 'Listar avaliacoes do vendedor', 'security' => $protected, 'responses' => $ok],
                ],
                '/listings' => [
                    'get' => ['tags' => ['Marketplace'], 'summary' => 'Listar anuncios', 'responses' => $ok],
                    'post' => ['tags' => ['Marketplace'], 'summary' => 'Criar anuncio de venda', 'security' => $protected, 'responses' => $created],
                ],
                '/listings/{id}' => [
                    'get' => ['tags' => ['Marketplace'], 'summary' => 'Detalhar anuncio', 'responses' => $ok],
                    'put' => ['tags' => ['Marketplace'], 'summary' => 'Atualizar anuncio', 'security' => $protected, 'responses' => $ok],
                    'delete' => ['tags' => ['Marketplace'], 'summary' => 'Excluir anuncio', 'security' => $protected, 'responses' => $deleted],
                ],
                '/orders' => [
                    'get' => ['tags' => ['Orders'], 'summary' => 'Historico de pedidos', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Orders'], 'summary' => 'Criar pedido', 'security' => $protected, 'responses' => $created],
                ],
                '/orders/{id}' => [
                    'get' => ['tags' => ['Orders'], 'summary' => 'Detalhar pedido', 'security' => $protected, 'responses' => $ok],
                ],
                '/exchanges' => [
                    'get' => ['tags' => ['Exchanges'], 'summary' => 'Listar trocas', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Exchanges'], 'summary' => 'Solicitar troca', 'security' => $protected, 'responses' => $created],
                ],
                '/exchanges/{id}/accept' => [
                    'put' => ['tags' => ['Exchanges'], 'summary' => 'Aceitar troca', 'security' => $protected, 'responses' => $ok],
                ],
                '/exchanges/{id}/reject' => [
                    'put' => ['tags' => ['Exchanges'], 'summary' => 'Rejeitar troca', 'security' => $protected, 'responses' => $ok],
                ],
                '/conversations' => [
                    'get' => ['tags' => ['Chat'], 'summary' => 'Listar conversas', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Chat'], 'summary' => 'Criar conversa', 'security' => $protected, 'responses' => $created],
                ],
                '/conversations/{id}/messages' => [
                    'get' => ['tags' => ['Chat'], 'summary' => 'Listar mensagens', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Chat'], 'summary' => 'Enviar mensagem', 'security' => $protected, 'responses' => $created],
                ],
                '/conversations/{id}/messages/{message}/read' => [
                    'put' => ['tags' => ['Chat'], 'summary' => 'Marcar mensagem como lida', 'security' => $protected, 'responses' => $ok],
                ],
                '/conversations/{id}/typing' => [
                    'post' => ['tags' => ['Chat'], 'summary' => 'Enviar evento de digitacao', 'security' => $protected, 'responses' => $ok],
                ],
                '/favorites' => [
                    'get' => ['tags' => ['Favorites'], 'summary' => 'Listar favoritos', 'security' => $protected, 'responses' => $ok],
                    'post' => ['tags' => ['Favorites'], 'summary' => 'Favoritar livro', 'security' => $protected, 'responses' => $created],
                ],
                '/favorites/{id}' => [
                    'delete' => ['tags' => ['Favorites'], 'summary' => 'Remover favorito', 'security' => $protected, 'responses' => $deleted],
                ],
                '/cart' => [
                    'get' => ['tags' => ['Cart'], 'summary' => 'Ver carrinho', 'security' => $protected, 'responses' => $ok],
                ],
                '/cart/items' => [
                    'post' => ['tags' => ['Cart'], 'summary' => 'Adicionar item ao carrinho', 'security' => $protected, 'responses' => $created],
                ],
                '/cart/items/{id}' => [
                    'delete' => ['tags' => ['Cart'], 'summary' => 'Remover item do carrinho', 'security' => $protected, 'responses' => $deleted],
                ],
                '/payments/checkout' => [
                    'post' => ['tags' => ['Payments'], 'summary' => 'Criar checkout Stripe', 'security' => $protected, 'responses' => $ok],
                ],
                '/payments/webhook' => [
                    'post' => ['tags' => ['Payments'], 'summary' => 'Webhook Stripe', 'responses' => $ok],
                ],
                '/payments/refund' => [
                    'post' => ['tags' => ['Payments'], 'summary' => 'Reembolsar pedido', 'security' => $protected, 'responses' => $ok],
                ],
                '/stripe/connect' => [
                    'post' => ['tags' => ['Payments'], 'summary' => 'Criar onboarding Stripe Connect', 'security' => $protected, 'responses' => $ok],
                ],
                '/stripe/account' => [
                    'get' => ['tags' => ['Payments'], 'summary' => 'Consultar conta Stripe Connect', 'security' => $protected, 'responses' => $ok],
                ],
                '/notifications' => [
                    'get' => ['tags' => ['Notifications'], 'summary' => 'Listar notificacoes', 'security' => $protected, 'responses' => $ok],
                ],
                '/notifications/{id}/read' => [
                    'put' => ['tags' => ['Notifications'], 'summary' => 'Marcar notificacao como lida', 'security' => $protected, 'responses' => $ok],
                ],
                '/admin/dashboard' => [
                    'get' => ['tags' => ['Admin'], 'summary' => 'Metricas administrativas', 'security' => $protected, 'responses' => $ok],
                ],
                '/uploads' => [
                    'post' => ['tags' => ['Uploads'], 'summary' => 'Upload de arquivo', 'security' => $protected, 'responses' => $created],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum token',
                    ],
                ],
                'schemas' => [
                    'JsonEnvelope' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => ['object', 'array', 'null']],
                            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
                            'message' => ['type' => 'string'],
                        ],
                    ],
                    'PaginationMeta' => [
                        'type' => 'object',
                        'properties' => [
                            'current_page' => ['type' => 'integer'],
                            'last_page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private static function bookFilters(): array
    {
        return collect(['search', 'title', 'author', 'isbn', 'publisher', 'category_id', 'condition', 'price_min', 'price_max', 'page', 'per_page'])
            ->map(fn (string $name): array => [
                'name' => $name,
                'in' => 'query',
                'required' => false,
                'schema' => ['type' => in_array($name, ['category_id', 'page', 'per_page'], true) ? 'integer' : 'string'],
            ])
            ->all();
    }
}
