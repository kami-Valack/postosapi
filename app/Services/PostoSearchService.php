<?php

namespace App\Services;

use App\Http\Resources\PostoPublicResource;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

class PostoSearchService
{
    /**
     * @param  array{q: string, limit?: int, tipo?: string, combustivel?: string, status?: string}  $params
     * @return array{q: string, count: int, postos: list<array<string, mixed>>}
     */
    public function search(array $params): array
    {
        $q = trim($params['q']);
        $limit = min(
            (int) ($params['limit'] ?? config('postos.search.default_limit', 20)),
            (int) config('postos.search.max_limit', 50)
        );
        $limit = max(1, $limit);

        $cacheKey = 'postos.search.'.md5(json_encode([
            'q' => mb_strtolower($q),
            'limit' => $limit,
            'tipo' => $params['tipo'] ?? null,
            'combustivel' => $params['combustivel'] ?? null,
            'status' => $params['status'] ?? null,
        ]));

        $ttl = (int) config('postos.search.cache_ttl_seconds', 60);

        return Cache::remember($cacheKey, $ttl, function () use ($q, $limit, $params) {
            $posts = $this->buildQuery($q, $params)->limit($limit)->get();

            return [
                'q' => $q,
                'count' => $posts->count(),
                'postos' => PostoPublicResource::collection($posts)->resolve(),
            ];
        });
    }

    /**
     * @param  array{tipo?: string, combustivel?: string, status?: string}  $filters
     */
    private function buildQuery(string $q, array $filters)
    {
        $query = Post::query()
            ->publicActive()
            ->select(Post::publicListColumns())
            ->searchTerm($q)
            ->with([
                'services' => fn ($relation) => $relation->select('services.id', 'services.name'),
            ])
            ->orderBy('name');

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['combustivel'])) {
            $query->where('combustivel', $filters['combustivel']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * Invalida cache de pesquisa (chamar após criar/actualizar postos).
     */
    public static function flushCache(): void
    {
        // Com driver file/database, tags podem não existir; TTL curto cobre a maioria dos casos.
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(['postos.search'])->flush();
        }
    }
}
