<?php

return [
    // Janela para calcular consumo médio (RN-G-004).
    'analysis_window_hours' => (int) env('STOCK_ANALYSIS_WINDOW_HOURS', 48),

    // Alertar se o stock acabar em menos de X horas (com base na média).
    'rupture_threshold_hours' => (int) env('STOCK_RUPURE_THRESHOLD_HOURS', 24),

    'min_history_points' => 2,

    'statuses' => [
        'active' => 'Activo',
        'acknowledged' => 'Reconhecido',
        'resolved' => 'Resolvido',
    ],
];
