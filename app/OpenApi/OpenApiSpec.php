<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Postos API',
    description: <<<'DESC'
API de gestão de postos (PinPoint): JWT, RBAC, stock e histórico.

**Rotas públicas (sem JWT):**
- `GET /postos` — listar, paginar (`?page=`) ou detalhe (`?id=`)
- `GET /postos/search` — pesquisa rápida (`?q=`, opcional `limit`, `tipo`, `combustivel`, `status`)

**Papéis locais (`users.role_id`):** ver `docs/ROLES.md` e `GET /api/roles`.
DESC,
    contact: new OA\Contact(email: 'suporte@postosapi.local')
)]
#[OA\Server(url: '/api', description: 'Prefixo das rotas API')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Token JWT no header Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Auth', description: 'Utilizador autenticado (JWT)')]
#[OA\Tag(name: 'Roles', description: 'Catálogo de papéis (IDs 1–4 definidos nesta API)')]
#[OA\Tag(name: 'Posts', description: 'Postos de abastecimento')]
#[OA\Tag(name: 'Stock', description: 'Stock por produto e posto')]
#[OA\Tag(name: 'StockHistory', description: 'Histórico de ajustes de stock')]
#[OA\Tag(name: 'Users', description: 'Utilizadores e associação a postos')]
#[OA\Tag(
    name: 'Postos Públicos',
    description: 'B2C sem autenticação: listagem (`/postos`), pesquisa (`/postos/search?q=`)'
)]
class OpenApiSpec
{
}
