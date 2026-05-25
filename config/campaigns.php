<?php

return [
    'default_radius_meters' => (int) env('CAMPAIGN_DEFAULT_RADIUS_METERS', 500),
    'max_radius_meters' => (int) env('CAMPAIGN_MAX_RADIUS_METERS', 5000),
    'max_duration_days' => (int) env('CAMPAIGN_MAX_DURATION_DAYS', 90),
    'max_discount_percent' => (float) env('CAMPAIGN_MAX_DISCOUNT_PERCENT', 30),

    'statuses' => ['draft', 'scheduled', 'active', 'paused', 'ended', 'cancelled'],

    'interaction_types' => ['view', 'click', 'conversion'],
];
