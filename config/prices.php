<?php

return [
    // Prazo em horas para confirmar um decreto sem ser considerado atraso (RN-G-001).
    'confirmation_deadline_hours' => (int) env('PRICE_CONFIRMATION_DEADLINE_HOURS', 48),
];
