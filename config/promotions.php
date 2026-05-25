<?php

return [
    // Desconto máximo permitido em promoções locais (RN-G-002), definido pelo admin.
    'max_discount_percent' => (float) env('PROMOTION_MAX_DISCOUNT_PERCENT', 25),

    'min_duration_hours' => 1,
    'max_duration_days' => (int) env('PROMOTION_MAX_DURATION_DAYS', 30),
];
