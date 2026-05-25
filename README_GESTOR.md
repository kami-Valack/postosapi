# Guia do Gestor — API Postos

Este README descreve o que o papel **Gestor** (gestor de posto) pode fazer na API, como se autenticar, endpoints relevantes, regras de autorização e exemplos de uso.

## Documentação da API (Swagger / OpenAPI)

- **Swagger UI:** `GET /api/documentation` (interface interativa; botão *Authorize* com `Bearer {token}`).
- **ReDoc:** `GET /api/docs` (leitura).
- **Spec JSON:** `GET /api/docs/openapi.json`.
- Regenerar após alterações: `composer swagger` ou `php artisan l5-swagger:generate`.

### Deploy com HTTPS (evitar erro mixed-content no Swagger)

Se a página abre em `https://` mas o spec pede `http://`, configure no `.env` do servidor:

```env
APP_URL=https://postos.pinpointech.com
FORCE_HTTPS=true
L5_SWAGGER_USE_ABSOLUTE_PATH=false
L5_SWAGGER_CONST_HOST=https://postos.pinpointech.com
```

Depois: `php artisan config:clear` (e reiniciar PHP-FPM / queue se aplicável).

O proxy (Nginx, Cloudflare, etc.) deve enviar `X-Forwarded-Proto: https` — a API já confia em proxies (`trustProxies`).

## Papéis (`role_id` nesta API)

| ID | Nome | Tipo |
|----|------|------|
| **1** | Super Admin Premium | admin |
| **2** | Super Admin | admin |
| **3** | Admin | admin |
| **4** | Gestor | gestor |

Documentação completa: [docs/ROLES.md](docs/ROLES.md). Listar: `GET /api/roles`.

Após deploy: `php artisan db:seed --class=RoleSeeder`

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

2. **Associar gestor ao posto** — só `role_id: 4` pode ter `post_id`:

```bash
curl -X PATCH "http://localhost:8000/api/users/5" \
  -H "Authorization: Bearer <JWT admin>" \
  -H "Content-Type: application/json" \
  -d '{"post_id":1,"role_id":4}'
```

O utilizador é **sincronizado automaticamente** no primeiro pedido com JWT válido (`auth_user_id` = `sub` do token). Para associar um gestor ao posto, use o `auth_user_id` ou o `id` local devolvido em `GET /api/me`. Utilizadores admin (1–3) **não** recebem `post_id`.

## Endpoints relevantes

- `GET /api/me`
  - Retorna o payload do JWT (informações do usuário).

- `GET /api/posts`
  - Lista posts. Se o gestor tem `post_id` associado, retorna apenas o post dele.

- `GET /api/posts/{post}`
  - Consulta detalhes de um post específico.

### Gestão operacional do posto (RN-G-004.1, RN-G-005)

- `GET /api/gestor/catalog` — catálogo de combustíveis (`gasolina`, `gasoleo`, `gpl`, `eletrico`) e serviços aprovados.
- `GET /api/posts/{post}/operational` — estado actual: `status`, serviços activos/inactivos, disponibilidade de combustíveis.
- `PATCH /api/posts/{post}/operational` — actualizar (só gestor do posto).

Exemplo de actualização:

```bash
curl -X PATCH "http://localhost:8000/api/posts/1/operational" \
  -H "Authorization: Bearer <JWT gestor>" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "aberto",
    "hours_24": true,
    "services": [
      {"name": "Wifi", "active": true},
      {"name": "Lavagem", "active": false, "motivo_desativacao": "equipamento avariado"}
    ],
    "combustiveis": [
      {"slug": "gasolina", "disponibilidade": "em_stock"},
      {"slug": "gasoleo", "disponibilidade": "fora_stock", "motivo_fora_stock": "rutura de stock"},
      {"slug": "eletrico", "disponibilidade": "em_stock"}
    ]
  }'
```

Regras:
- `status`: `aberto`, `fechado` ou `manutencao`.
- Serviço inactivo exige `motivo_desativacao`.
- Combustível `fora_stock` exige `motivo_fora_stock`.
- Alterações ficam em `audit_logs` e histórico de combustível em `fuel_availability_histories`.

### Preços decretados (RN-G-001)

O gestor **não define preços manualmente** — apenas **confirma** decretos publicados pelo admin (ANPG/IRDP).

- `GET /api/posts/{post}/price-decrees` — decretos pendentes ou já confirmados no teu posto.
- `POST /api/posts/{post}/price-decrees/{id}/confirm` — confirma aplicação; actualiza `preco` / `precoPremium` no posto.

```bash
# Listar
curl "http://localhost:8000/api/posts/1/price-decrees" \
  -H "Authorization: Bearer <JWT gestor>"

# Confirmar (com motivo se estiver em atraso)
curl -X POST "http://localhost:8000/api/posts/1/price-decrees/3/confirm" \
  -H "Authorization: Bearer <JWT gestor>" \
  -H "Content-Type: application/json" \
  -d '{"motivo_atraso": "problema técnico"}'
```

Estados: `pendente`, `pendente_atrasado` (exige `motivo_atraso`), `confirmado`.

### Incidentes e manutenção (RN-G-006)

Reportar avarias, falhas de energia, problemas em serviços ou carregadores EV.

- `GET /api/gestor/catalog` — inclui `incidentes.categories`, `equipment_types`, etc.
- `GET /api/posts/{post}/incidents` — listar (`?status=aberto` opcional).
- `POST /api/posts/{post}/incidents` — criar (**multipart/form-data**, fotos opcionais).
- `GET /api/posts/{post}/incidents/{id}` — detalhe.

Campos principais:
- `category`: `urgente` | `normal`
- `equipment_type`: `bomba` | `servico` | `energia` | `ev_charger` | `outro`
- `service_id` — obrigatório se `equipment_type=servico` (serviço do posto)
- `fuel_type_id` — opcional (ex. bomba de gasolina)
- `description` — mínimo 10 caracteres
- `photos[]` — até 5 imagens (jpg/png/webp)

```bash
curl -X POST "http://localhost:8000/api/posts/1/incidents" \
  -H "Authorization: Bearer <JWT gestor>" \
  -F "category=urgente" \
  -F "equipment_type=bomba" \
  -F "fuel_type_id=1" \
  -F "title=Bomba 2" \
  -F "description=Falha na bomba de gasolina durante abastecimento" \
  -F "photos[]=@/caminho/foto1.jpg"
```

**Admin:** `GET /api/admin/incidents`, `PATCH /api/admin/incidents/{id}` com `status` e `admin_notes`.

Fotos: executar `php artisan storage:link` para URLs públicas em `/storage/...`.

### Promoções locais (RN-G-002)

Promoções de curta duração para **serviços** ou **produtos não-combustível** do posto. Não se aplicam a combustíveis/energia. O desconto máximo é definido pelo admin (`PROMOTION_MAX_DISCOUNT_PERCENT`, default 25%).

- `GET /api/posts/{post}/promotions` — listar (`?status=active` opcional).
- `POST /api/posts/{post}/promotions` — criar (indicar `service_id` **ou** `product_id`).
- `PATCH /api/posts/{post}/promotions/{id}` — editar (antes de terminar).
- `POST /api/posts/{post}/promotions/{id}/cancel` — cancelar.

```bash
curl -X POST "http://localhost:8000/api/posts/1/promotions" \
  -H "Authorization: Bearer <JWT gestor>" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Lavagem -15%",
    "service_id": 3,
    "discount_percent": 15,
    "starts_at": "2026-05-24T08:00:00+01:00",
    "ends_at": "2026-05-26T20:00:00+01:00"
  }'
```

**B2C:** `GET /api/postos?id=1` inclui `promocoes[]` com promoções activas (cache ~60s).

Comando agendado: `php artisan promotions:sync-status` (activa/termina por data).

### Alertas preditivos de rutura (RN-G-004)

Analisa `stock_histories` (janela configurável, default 48h) e o stock actual para prever rutura. Complementa o nível crítico (RN-G-003).

- `GET /api/posts/{post}/stock-alerts` — alertas abertos (`active` / `acknowledged`).
- `POST /api/posts/{post}/stock-alerts/analyze` — forçar análise imediata.
- `PATCH /api/posts/{post}/stock-alerts/{id}/acknowledge` — reconhecer alerta.

Variáveis `.env`: `STOCK_ANALYSIS_WINDOW_HOURS=48`, `STOCK_RUPURE_THRESHOLD_HOURS=24`.

Comando agendado: `php artisan stock:analyze-rupture-risk` (hourly). Após cada `PATCH` de stock, a API também dispara análise para esse produto.

### Campanhas geolocalizadas (RN-G-007 / RN-G-007.1)

Campanhas de marketing para serviços/produtos **não-combustível**, com raio geográfico em metros a partir do posto.

- `GET /api/posts/{post}/campaigns` — listar.
- `POST /api/posts/{post}/campaigns` — criar (`radius_meters`, `budget_amount` opcional).
- `PATCH /api/posts/{post}/campaigns/{id}` — editar.
- `GET /api/posts/{post}/campaigns/{id}/performance` — views, cliques, conversões, CTR, ROI estimado.
- `PATCH /api/posts/{post}/campaigns/{id}/feedback` — `feedback_qualitativo` (RN-G-007.1).
- `POST .../pause`, `POST .../resume`, `POST .../cancel`.

**B2C (sem JWT):**
- `GET /api/campaigns/nearby?latitude=&longitude=` — campanhas no raio.
- `POST /api/campaigns/{id}/interactions` — `event_type`: view | click | conversion + coordenadas.

`GET /api/postos?id=1` inclui `campanhas[]` activas.

### Operação offline e sincronização (RN-G-008)

A app gestor guarda operações localmente e envia em lote quando houver rede:

- `POST /api/posts/{post}/sync` — processar lote.
- `GET /api/posts/{post}/sync/batches` — histórico + tipos permitidos (`GET /api/gestor/catalog` → `offline_sync`).
- `GET /api/posts/{post}/sync/batches/{id}` — detalhe por operação.

Tipos suportados: `stock.update`, `operational.update`, `incident.create`, `price_decree.confirm`, `promotion.create`, `campaign.create`.

Cada operação exige: `id` (UUID), `type`, `payload`, `client_timestamp`. Resposta por operação: `applied`, `conflict`, `rejected` ou `duplicate` (idempotência).

```bash
curl -X POST "http://localhost:8000/api/posts/1/sync" \
  -H "Authorization: Bearer <JWT gestor>" \
  -H "Content-Type: application/json" \
  -d '{
    "device_id": "tablet-posto-1",
    "client_batch_at": "2026-05-24T14:30:00+01:00",
    "operations": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "type": "stock.update",
        "client_timestamp": "2026-05-24T14:25:00+01:00",
        "payload": {
          "product_id": 42,
          "quantity": 80,
          "justificativa_ajuste": "Contagem offline"
        }
      }
    ]
  }'
```

Conflitos: stock/operacional usam `last_write_wins` por defeito; confirmação de preço usa `admin_wins` se já confirmado no servidor.

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
  - É executada análise preditiva (RN-G-004); a resposta pode incluir `rupture_alert` se houver risco de rutura.

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


