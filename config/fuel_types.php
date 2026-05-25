<?php

/**
 * Catálogo canónico de combustíveis/energia (RN-G-004.1).
 * IDs fixos após seed — alinhados com fuel_types.id.
 */
return [
    'definitions' => [
        1 => ['slug' => 'gasolina', 'name' => 'Gasolina', 'sort_order' => 1],
        2 => ['slug' => 'gasoleo', 'name' => 'Gasóleo', 'sort_order' => 2],
        3 => ['slug' => 'gpl', 'name' => 'GPL', 'sort_order' => 3],
        4 => ['slug' => 'eletrico', 'name' => 'Elétrico', 'sort_order' => 4],
    ],
    'availability' => [
        'em_stock' => 'Em stock',
        'fora_stock' => 'Fora de stock',
    ],
    'post_status' => [
        'aberto',
        'fechado',
        'manutencao',
    ],
];
