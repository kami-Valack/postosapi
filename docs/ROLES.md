# Papéis (roles) — Postos API

Esta API define **IDs fixos** para papéis na tabela `roles`. Use sempre estes números em `users.role_id` e em `PATCH /api/users/{user}`.

> **Nota:** O serviço de autenticação externo pode usar outros IDs. Aqui importa o **`role_id` local** após sync (por nome) ou o valor que um admin atribui explicitamente.

## Tabela de referência

| `role_id` | Nome | Tipo | Uso na API |
|-----------|------|------|------------|
| **1** | Super Admin Premium | admin | Criar/editar/apagar postos; associar **gestores** a postos |
| **2** | Super Admin | admin | Idem |
| **3** | Admin | admin | Idem |
| **4** | Gestor | gestor | Único papel que pode ter `users.post_id`; stock e histórico do posto |

> **Associação posto ↔ utilizador:** só `role_id = 4` (Gestor) pode receber `post_id`. Admins (1–3) nunca são ligados a um posto.

## Exemplos

Associar utilizador como gestor do posto `1`:

```http
PATCH /api/users/1
Authorization: Bearer <JWT de admin (role_id 1, 2 ou 3)>
Content-Type: application/json

{
  "post_id": 1,
  "role_id": 4
}
```

Se o utilizador for admin (1–3), `post_id` é ignorado/rejeitado. Ao promover alguém de gestor para admin, o `post_id` é removido automaticamente.

Listar papéis na base de dados:

```http
GET /api/roles
```

Consultar o catálogo em código: `config/roles.php` ou `App\Support\RoleIds`.

## Seed

Após deploy ou migração:

```bash
php artisan db:seed --class=RoleSeeder
```
