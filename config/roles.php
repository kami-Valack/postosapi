<?php

/**
 * Papéis canónicos desta API (Postos API).
 * Os IDs são definidos aqui — não dependem do serviço de auth externo.
 *
 * Ao associar utilizadores use users.role_id com estes valores.
 */
return [
    'definitions' => [
        1 => [
            'name' => 'Super Admin Premium',
            'type' => 'admin',
            'description' => 'Administração total da plataforma de postos.',
        ],
        2 => [
            'name' => 'Super Admin',
            'type' => 'admin',
            'description' => 'Administração de postos, utilizadores e configuração.',
        ],
        3 => [
            'name' => 'Admin',
            'type' => 'admin',
            'description' => 'Gestão operacional de postos e associação de gestores.',
        ],
        4 => [
            'name' => 'Gestor',
            'type' => 'gestor',
            'description' => 'Operação do posto atribuído (stock, histórico); requer users.post_id.',
        ],
    ],
];
