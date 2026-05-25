<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Stock;
use App\Models\StockHistory;
use App\Models\StockRuptureAlert;
use Illuminate\Support\Collection;

class StockRuptureAnalysisService
{
    /**
     * Analisa todos os stocks de um posto (produtos não-combustível).
     *
     * @return list<StockRuptureAlert>
     */
    public function analyzePost(Post $post): array
    {
        $created = [];
        $stocks = Stock::query()
            ->where('post_id', $post->id)
            ->whereHas('product', fn ($q) => $q->where('is_combustible', false))
            ->with('product')
            ->get();

        foreach ($stocks as $stock) {
            $alert = $this->analyzeStock($stock);
            if ($alert) {
                $created[] = $alert;
            }
        }

        return $created;
    }

    /**
     * Analisa todos os postos.
     */
    public function analyzeAll(): int
    {
        $count = 0;
        Post::query()->where('is_active', true)->pluck('id')->each(function (int $postId) use (&$count) {
            $post = Post::find($postId);
            if ($post) {
                $count += count($this->analyzePost($post));
            }
        });

        return $count;
    }

    public function analyzeStock(Stock $stock): ?StockRuptureAlert
    {
        $stock->loadMissing('product');
        if ($stock->product?->is_combustible) {
            return null;
        }

        $windowHours = (int) config('stock_alerts.analysis_window_hours', 48);
        $thresholdHours = (float) config('stock_alerts.rupture_threshold_hours', 24);
        $minPoints = (int) config('stock_alerts.min_history_points', 2);

        $since = now()->subHours($windowHours);
        $histories = StockHistory::query()
            ->where('stock_id', $stock->id)
            ->where('created_at', '>=', $since)
            ->orderBy('created_at')
            ->get();

        $currentQty = (float) $stock->quantity;
        $criticalLevel = $stock->critical_level !== null ? (float) $stock->critical_level : null;

        $avgHourly = $this->averageHourlyConsumption($histories);

        $hoursUntil = null;
        $predictedAt = null;
        if ($avgHourly !== null && $avgHourly > 0) {
            $hoursUntil = $currentQty / $avgHourly;
            $predictedAt = now()->addHours($hoursUntil);
        }

        $shouldAlert = false;
        $severity = 'warning';

        if ($criticalLevel !== null && $currentQty <= $criticalLevel) {
            $shouldAlert = true;
            $severity = 'critical';
        } elseif ($hoursUntil !== null && $hoursUntil <= $thresholdHours) {
            $shouldAlert = true;
            $severity = $hoursUntil <= ($thresholdHours / 2) ? 'critical' : 'warning';
        } elseif ($avgHourly === null && $histories->count() < $minPoints && $criticalLevel !== null && $currentQty <= $criticalLevel * 1.2) {
            $shouldAlert = true;
            $severity = 'warning';
        }

        if (! $shouldAlert) {
            $this->resolveOpenAlertsIfRecovered($stock);

            return null;
        }

        return $this->upsertAlert($stock, $currentQty, $avgHourly, $hoursUntil, $predictedAt, $severity);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForPost(Post $post, ?string $status = null): Collection
    {
        $query = StockRuptureAlert::query()
            ->where('post_id', $post->id)
            ->with(['product', 'stock'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->open();
        }

        return $query->get()->map(fn (StockRuptureAlert $a) => $this->format($a));
    }

    public function acknowledge(StockRuptureAlert $alert, int $userId): StockRuptureAlert
    {
        $alert->status = 'acknowledged';
        $alert->acknowledged_by = $userId;
        $alert->acknowledged_at = now();
        $alert->save();

        return $alert->load(['product', 'stock']);
    }

    public function resolve(StockRuptureAlert $alert): StockRuptureAlert
    {
        $alert->status = 'resolved';
        $alert->resolved_at = now();
        $alert->save();

        return $alert;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, StockHistory>  $histories
     */
    private function averageHourlyConsumption(Collection $histories): ?float
    {
        if ($histories->count() < (int) config('stock_alerts.min_history_points', 2)) {
            return null;
        }

        $totalConsumed = 0.0;
        $firstAt = null;
        $lastAt = null;

        foreach ($histories as $row) {
            $old = $row->old_quantity !== null ? (float) $row->old_quantity : null;
            $new = $row->new_quantity !== null ? (float) $row->new_quantity : null;
            if ($old === null || $new === null || $new >= $old) {
                continue;
            }
            $totalConsumed += $old - $new;
            $firstAt = $firstAt ?? $row->created_at;
            $lastAt = $row->created_at;
        }

        if ($totalConsumed <= 0 || ! $firstAt || ! $lastAt) {
            return null;
        }

        $hours = max(1.0, $firstAt->diffInMinutes($lastAt) / 60);

        return $totalConsumed / $hours;
    }

    private function upsertAlert(
        Stock $stock,
        float $currentQty,
        ?float $avgHourly,
        ?float $hoursUntil,
        ?\Carbon\Carbon $predictedAt,
        string $severity
    ): StockRuptureAlert {
        $existing = StockRuptureAlert::query()
            ->where('post_id', $stock->post_id)
            ->where('product_id', $stock->product_id)
            ->open()
            ->first();

        $payload = [
            'stock_id' => $stock->id,
            'current_quantity' => $currentQty,
            'avg_hourly_consumption' => $avgHourly,
            'hours_until_rupture' => $hoursUntil,
            'predicted_rupture_at' => $predictedAt,
            'severity' => $severity,
            'status' => 'active',
        ];

        if ($existing) {
            $existing->fill($payload);
            $existing->save();

            return $existing->load(['product', 'stock']);
        }

        return StockRuptureAlert::query()->create(array_merge($payload, [
            'post_id' => $stock->post_id,
            'product_id' => $stock->product_id,
        ]))->load(['product', 'stock']);
    }

    private function resolveOpenAlertsIfRecovered(Stock $stock): void
    {
        StockRuptureAlert::query()
            ->where('post_id', $stock->post_id)
            ->where('product_id', $stock->product_id)
            ->open()
            ->each(fn (StockRuptureAlert $a) => $this->resolve($a));
    }

    /**
     * @return array<string, mixed>
     */
    public function format(StockRuptureAlert $alert): array
    {
        return [
            'id' => $alert->id,
            'post_id' => $alert->post_id,
            'product' => $alert->product ? [
                'id' => $alert->product->id,
                'name' => $alert->product->name,
                'unit' => $alert->product->unit,
            ] : null,
            'current_quantity' => (float) $alert->current_quantity,
            'avg_hourly_consumption' => $alert->avg_hourly_consumption !== null
                ? (float) $alert->avg_hourly_consumption
                : null,
            'hours_until_rupture' => $alert->hours_until_rupture !== null
                ? (float) $alert->hours_until_rupture
                : null,
            'predicted_rupture_at' => $alert->predicted_rupture_at?->toIso8601String(),
            'severity' => $alert->severity,
            'status' => $alert->status,
            'acknowledged_at' => $alert->acknowledged_at?->toIso8601String(),
            'created_at' => $alert->created_at?->toIso8601String(),
        ];
    }
}
