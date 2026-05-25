<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\OfflineSyncBatch;
use App\Models\OfflineSyncOperation;
use App\Models\Post;
use App\Models\PostPriceConfirmation;
use App\Models\PriceDecree;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OfflineSyncService
{
    public function __construct(
        private readonly GestorPostOperationalService $operationalService,
        private readonly IncidentService $incidentService,
        private readonly PriceDecreeService $priceDecreeService,
        private readonly PromotionService $promotionService,
        private readonly CampaignService $campaignService,
        private readonly StockRuptureAnalysisService $ruptureAnalysis
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function processBatch(Post $post, User $user, array $data): array
    {
        $operations = $data['operations'] ?? [];
        $max = (int) config('sync.max_operations_per_batch', 50);
        if (count($operations) > $max) {
            throw ValidationException::withMessages([
                'operations' => ["Máximo de {$max} operações por lote."],
            ]);
        }

        $batch = OfflineSyncBatch::query()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'device_id' => $data['device_id'] ?? null,
            'status' => 'processing',
            'operations_total' => count($operations),
            'client_batch_at' => isset($data['client_batch_at'])
                ? \Carbon\Carbon::parse($data['client_batch_at'])
                : now(),
        ]);

        $results = [];
        $applied = 0;
        $conflicted = 0;
        $rejected = 0;

        foreach ($operations as $op) {
            $result = $this->processOperation($post, $user, $batch, $op);
            $results[] = $result;
            match ($result['status']) {
                'applied', 'duplicate' => $applied++,
                'conflict' => $conflicted++,
                default => $rejected++,
            };
        }

        $status = match (true) {
            $conflicted > 0 && $applied > 0 => 'partial',
            $conflicted > 0 || $rejected > 0 => $conflicted + $rejected === count($operations) ? 'completed' : 'partial',
            default => 'completed',
        };

        $batch->update([
            'status' => $status,
            'operations_applied' => $applied,
            'operations_conflicted' => $conflicted,
            'operations_rejected' => $rejected,
            'processed_at' => now(),
        ]);

        AuditLog::record($user->id, 'gestor.offline_sync.batch', OfflineSyncBatch::class, $batch->id, null, [
            'operations_total' => $batch->operations_total,
            'applied' => $applied,
            'conflicted' => $conflicted,
            'rejected' => $rejected,
        ]);

        return [
            'batch_id' => $batch->id,
            'status' => $batch->status,
            'summary' => [
                'total' => count($operations),
                'applied' => $applied,
                'conflicted' => $conflicted,
                'rejected' => $rejected,
            ],
            'operations' => $results,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listBatches(Post $post, int $limit = 20): \Illuminate\Support\Collection
    {
        return OfflineSyncBatch::query()
            ->where('post_id', $post->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (OfflineSyncBatch $b) => $this->formatBatch($b));
    }

    /**
     * @return array<string, mixed>
     */
    public function showBatch(OfflineSyncBatch $batch): array
    {
        $batch->load('operations');

        return array_merge($this->formatBatch($batch), [
            'operations' => $batch->operations->map(fn (OfflineSyncOperation $op) => [
                'client_operation_id' => $op->client_operation_id,
                'operation_type' => $op->operation_type,
                'status' => $op->status,
                'conflict_reason' => $op->conflict_reason,
                'client_timestamp' => $op->client_timestamp?->toIso8601String(),
                'server_result' => $op->server_result,
            ])->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $op
     * @return array<string, mixed>
     */
    private function processOperation(Post $post, User $user, OfflineSyncBatch $batch, array $op): array
    {
        $clientId = $op['id'] ?? $op['client_operation_id'] ?? null;
        $type = $op['type'] ?? $op['operation_type'] ?? null;
        $payload = $op['payload'] ?? [];
        $clientTs = isset($op['client_timestamp'])
            ? \Carbon\Carbon::parse($op['client_timestamp'])
            : null;

        if (! $clientId || ! $type || ! $clientTs) {
            return $this->recordOperation($batch, $clientId ?? (string) \Str::uuid(), $type ?? 'unknown', $payload, $clientTs ?? now(), 'rejected', 'Campos id, type e client_timestamp obrigatórios.', null);
        }

        if (! in_array($type, config('sync.allowed_operations', []), true)) {
            return $this->recordOperation($batch, $clientId, $type, $payload, $clientTs, 'rejected', 'Tipo de operação não permitido.', null);
        }

        $duplicate = OfflineSyncOperation::query()
            ->whereHas('batch', fn ($q) => $q->where('post_id', $post->id))
            ->where('client_operation_id', $clientId)
            ->where('status', 'applied')
            ->first();

        if ($duplicate) {
            return $this->recordOperation($batch, $clientId, $type, $payload, $clientTs, 'duplicate', null, $duplicate->server_result);
        }

        try {
            $serverResult = $this->applyOperation($post, $user, $type, $payload, $clientTs);

            return $this->recordOperation($batch, $clientId, $type, $payload, $clientTs, 'applied', null, $serverResult);
        } catch (ValidationException $e) {
            $msg = collect($e->errors())->flatten()->first() ?? $e->getMessage();

            return $this->recordOperation($batch, $clientId, $type, $payload, $clientTs, 'rejected', $msg, null);
        } catch (SyncConflictException $e) {
            return $this->recordOperation($batch, $clientId, $type, $payload, $clientTs, 'conflict', $e->getMessage(), $e->serverState);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyOperation(Post $post, User $user, string $type, array $payload, \Carbon\Carbon $clientTs): array
    {
        return match ($type) {
            'stock.update' => $this->applyStockUpdate($post, $user, $payload, $clientTs),
            'operational.update' => $this->applyOperationalUpdate($post, $user, $payload, $clientTs),
            'incident.create' => $this->applyIncidentCreate($post, $user, $payload),
            'price_decree.confirm' => $this->applyPriceConfirm($post, $user, $payload),
            'promotion.create' => $this->applyPromotionCreate($post, $user, $payload),
            'campaign.create' => $this->applyCampaignCreate($post, $user, $payload),
            default => throw ValidationException::withMessages(['type' => ['Operação desconhecida.']]),
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyStockUpdate(Post $post, User $user, array $payload, \Carbon\Carbon $clientTs): array
    {
        $productId = $payload['product_id'] ?? null;
        if (! $productId) {
            throw ValidationException::withMessages(['product_id' => ['Obrigatório.']]);
        }

        $stock = Stock::query()->where('post_id', $post->id)->where('product_id', $productId)->first();
        $strategy = config('sync.conflict_strategies.stock.update', 'last_write_wins');

        if ($stock && $strategy === 'last_write_wins') {
            $lastHistory = StockHistory::query()
                ->where('stock_id', $stock->id)
                ->orderByDesc('created_at')
                ->first();
            if ($lastHistory && $lastHistory->created_at->gt($clientTs)) {
                throw new SyncConflictException(
                    'Stock no servidor é mais recente que o registo offline.',
                    ['quantity' => (float) $stock->quantity, 'updated_at' => $lastHistory->created_at->toIso8601String()]
                );
            }
        }

        $stock = Stock::firstOrNew(['post_id' => $post->id, 'product_id' => $productId]);
        $old = $stock->quantity ?? 0;
        $stock->quantity = $payload['quantity'];
        if (array_key_exists('critical_level', $payload)) {
            $stock->critical_level = $payload['critical_level'];
        }
        $stock->save();

        StockHistory::query()->create([
            'stock_id' => $stock->id,
            'old_quantity' => $old,
            'new_quantity' => $stock->quantity,
            'user_id' => $user->id,
            'justificativa_ajuste' => $payload['justificativa_ajuste'] ?? 'Sincronização offline (RN-G-008)',
        ]);

        $alert = $this->ruptureAnalysis->analyzeStock($stock->fresh());

        return [
            'stock_id' => $stock->id,
            'quantity' => (float) $stock->quantity,
            'rupture_alert' => $alert ? $this->ruptureAnalysis->format($alert) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyOperationalUpdate(Post $post, User $user, array $payload, \Carbon\Carbon $clientTs): array
    {
        if ($post->updated_at->gt($clientTs)) {
            throw new SyncConflictException(
                'Estado operacional no servidor é mais recente.',
                $this->operationalService->show($post)
            );
        }

        return $this->operationalService->update($post, $user, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyIncidentCreate(Post $post, User $user, array $payload): array
    {
        $incident = $this->incidentService->create($post, $user, $payload, null);

        return ['incident_id' => $incident->id, 'status' => $incident->status];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyPriceConfirm(Post $post, User $user, array $payload): array
    {
        $decreeId = $payload['price_decree_id'] ?? null;
        if (! $decreeId) {
            throw ValidationException::withMessages(['price_decree_id' => ['Obrigatório.']]);
        }

        if (PostPriceConfirmation::query()
            ->where('post_id', $post->id)
            ->where('price_decree_id', $decreeId)
            ->exists()) {
            throw new SyncConflictException(
                'Decreto já confirmado no servidor (admin_wins).',
                ['price_decree_id' => $decreeId, 'already_confirmed' => true]
            );
        }

        $decree = PriceDecree::query()->findOrFail($decreeId);

        return $this->priceDecreeService->confirm($post, $decree, $user, $payload['motivo_atraso'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyPromotionCreate(Post $post, User $user, array $payload): array
    {
        $promotion = $this->promotionService->create($post, $user, $payload);

        return ['promotion_id' => $promotion->id];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function applyCampaignCreate(Post $post, User $user, array $payload): array
    {
        $campaign = $this->campaignService->create($post, $user, $payload);

        return ['campaign_id' => $campaign->id];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $serverResult
     * @return array<string, mixed>
     */
    private function recordOperation(
        OfflineSyncBatch $batch,
        string $clientId,
        string $type,
        array $payload,
        \Carbon\Carbon $clientTs,
        string $status,
        ?string $reason,
        ?array $serverResult
    ): array {
        OfflineSyncOperation::query()->create([
            'offline_sync_batch_id' => $batch->id,
            'client_operation_id' => $clientId,
            'operation_type' => $type,
            'payload' => $payload,
            'client_timestamp' => $clientTs,
            'status' => $status,
            'conflict_reason' => $reason,
            'server_result' => $serverResult,
        ]);

        return [
            'client_operation_id' => $clientId,
            'operation_type' => $type,
            'status' => $status,
            'conflict_reason' => $reason,
            'server_result' => $serverResult,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBatch(OfflineSyncBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'post_id' => $batch->post_id,
            'device_id' => $batch->device_id,
            'status' => $batch->status,
            'operations_total' => $batch->operations_total,
            'operations_applied' => $batch->operations_applied,
            'operations_conflicted' => $batch->operations_conflicted,
            'operations_rejected' => $batch->operations_rejected,
            'client_batch_at' => $batch->client_batch_at?->toIso8601String(),
            'processed_at' => $batch->processed_at?->toIso8601String(),
            'created_at' => $batch->created_at?->toIso8601String(),
        ];
    }
}
