<?php

return [
    'max_operations_per_batch' => (int) env('SYNC_MAX_OPERATIONS', 50),

    // Resolução de conflitos: last_write_wins | server_wins | admin_wins
    'conflict_strategies' => [
        'stock.update' => env('SYNC_CONFLICT_STOCK', 'last_write_wins'),
        'operational.update' => env('SYNC_CONFLICT_OPERATIONAL', 'last_write_wins'),
        'incident.create' => 'server_wins',
        'price_decree.confirm' => 'admin_wins',
        'promotion.create' => 'last_write_wins',
        'campaign.create' => 'last_write_wins',
    ],

    'allowed_operations' => [
        'stock.update',
        'operational.update',
        'incident.create',
        'price_decree.confirm',
        'promotion.create',
        'campaign.create',
    ],
];
