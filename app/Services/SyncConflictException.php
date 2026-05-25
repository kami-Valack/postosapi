<?php

namespace App\Services;

use RuntimeException;

class SyncConflictException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $serverState
     */
    public function __construct(
        string $message,
        public readonly ?array $serverState = null
    ) {
        parent::__construct($message);
    }
}
