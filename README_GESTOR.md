# Guia do Gestor — API Postos

Este README descreve o que o papel **Gestor** (gestor de posto) pode fazer na API, como se autenticar, endpoints relevantes, regras de autorização e exemplos de uso.

## Documentação da API (Swagger / OpenAPI)

- **Swagger UI:** `GET /api/documentation` (interface interativa; botão *Authorize* com `Bearer {token}`).
- **ReDoc:** `GET /api/docs` (leitura).
- **Spec JSON:** `GET /api/docs/openapi.json`.
- Regenerar após alterações: `composer swagger` ou `php artisan l5-swagger:generate`.

## Autenticação externa (JWT)

- O login é feito noutro serviço (ex. `users.pinpointech.com`); esta API **não** tem endpoint de login.
- Envie o campo **`jwt`** da resposta de login no header `Authorization: Bearer …`.
- O `.env` desta API deve usar o mesmo `SECRETJWT` (ou chave pública RS256) que assina o token.
- No primeiro pedido autenticado, a API cria/atualiza o registo em `users` com `auth_user_id` = claim `sub` do JWT.

## Visão geral

- O Gestor é um usuário associado a um `post` (posto). Seu `User.post_id` determina a qual posto ele pertence.
- Todas as chamadas à API exigem autenticação via JWT (Bearer token). A claim `sub` do token deve conter o `id` do usuário.
- A API usa resposta JSON padronizada; erros retornam payloads JSON com `success: false`, `message` e `code`.

## Permissões principais do Gestor

- Atualizar o estoque de produtos apenas para o `post` ao qual está vinculado.
- Visualizar informações do próprio usuário através de `/api/me` (payload do JWT).
- Consultar lista de posts; se o usuário for gestor com `post_id` preenchido, a listagem de posts retorna apenas o seu posto.

Observação: ações de gerenciamento de posts (criar, alterar, deletar) são restritas a usuários com papel `admin`.

## Admin: criar posto e associar gestor

1. **Criar posto** (papel admin no JWT: `admin`, `Super Admin`, etc.):

```bash
curl -X POST "http://localhost:8000/api/posts" \
  -H "Authorization: Bearer <JWT>" \
  -H "Content-Type: application/json" \
  -d '{"name":"Posto Luanda Centro","address":"Rua X","is_active":true}'
```

2. **Associar utilizador ao posto** (`post_id` na tabela `users`):

```bash
curl -X PATCH "http://localhost:8000/api/users/5" \
  -H "Authorization: Bearer <JWT>" \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"role_id":3}'
```

O utilizador é **sincronizado automaticamente** no primeiro pedido com JWT válido (`auth_user_id` = `sub` do token). Para associar um gestor ao posto, use o `auth_user_id` (ex.: `1` para Alice) ou o `id` local devolvido em `GET /api/me`.

## Endpoints relevantes

- `GET /api/me`
  - Retorna o payload do JWT (informações do usuário).

- `GET /api/posts`
  - Lista posts. Se o gestor tem `post_id` associado, retorna apenas o post dele.

- `GET /api/posts/{post}`
  - Consulta detalhes de um post específico.

- `PATCH /api/posts/{post}/products/{product}/stock`
  - Atualiza o estoque do `product` no `post` especificado.
  - Requer autenticação e papel `gestor`.
  - O gestor só pode atualizar o estoque se pertencer ao `post` (ou seja, `user.post_id === post.id`).

## Regras e comportamento ao atualizar estoque

- Validação e payload
  - O request deve ser um `PATCH` com `Content-Type: application/json`.
  - Campos aceitos (exemplo):
    - `quantity` (inteiro) — nova quantidade.
    - `critical_level` (opcional, inteiro) — nível crítico do produto.
    - `justificativa_ajuste` (opcional, string) — justificativa para o ajuste (gravada no histórico).

- Efeitos colaterais
  - Ao alterar o estoque, a API cria um registro em `stock_histories` (ou `StockHistory`) contendo: `stock_id`, `old_quantity`, `new_quantity`, `user_id`, `justificativa_ajuste`.
  - As alterações são auditadas (registro de auditoria disponível em `audit_logs`).

- Autorização
  - Além de exigir papel `gestor`, a API checa que `user.post_id === post.id`. Se essa condição falhar, é retornado `403 Forbidden`.

## Autenticação JWT

- A API aceita tokens assinados com `HS256` (SECRETJWT) ou `RS256` (par de chaves pública/privada) conforme configuração (`JWT_ALGO`).
- O middleware `VerifyJwtMiddleware` decodifica o token e resolve o usuário com base na claim `sub`. Após isso, o usuário fica disponível via `auth()->user()` e `$request->user()`.

## Exemplo de request (curl)

Atualizar estoque de um produto (exemplo):

```bash
curl -X PATCH "http://localhost:8000/api/posts/1/products/42/stock" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer <SEU_TOKEN_JWT>" \
  -d '{"quantity": 150, "critical_level": 20, "justificativa_ajuste": "Inventário mensal"}'
```

Exemplo de corpo de resposta (sucesso):

```json
{
  "message": "Stock updated",
  "stock": {
    "id": 10,
    "post_id": 1,
    "product_id": 42,
    "quantity": 150,
    "critical_level": 20
  }
}
```

Erros retornam JSON padrão. Exemplo (sem permissão):

```json
{
  "success": false,
  "message": "Forbidden",
  "code": 403
}
```

## Boas práticas para Gestores

- Sempre envie `justificativa_ajuste` ao alterar quantidades para facilitar auditoria.
- Use tokens com escopo e expiração adequados; não compartilhe tokens.
- Teste primeiro em ambiente de homologação antes de alterar estoque em produção.

## Perguntas frequentes / Observações técnicas

- Q: Um gestor pode alterar o estoque de outro posto?
  - A: Não. Só pode alterar o estoque do posto ao qual está vinculado.

- Q: O que um administrador pode fazer que o gestor não pode?
  - A: Administradores podem criar, editar e deletar posts (`/api/posts` POST/PUT/DELETE) e têm permissões mais amplas.

- Q: Como ver o histórico de alterações de estoque?
  - A: Os ajustes criam entradas em `stock_histories`. A API deve expor endpoints para consultar esse histórico (se não houver, peça para eu adicionar).

## Próximos passos sugeridos

- Adicionar endpoints para listar `stock_histories` por `post` e por `product`.
- Criar documentação automatizada (Swagger/OpenAPI) para disponibilizar descrições e exemplos de todos os endpoints.

---

Arquivo gerado automaticamente para orientar o uso do papel `gestor`. Se quiser, posso:
- Adicionar esse README ao README principal do projeto.
- Incluir endpoints adicionais (histórico, confirmações de preço, promoções).


