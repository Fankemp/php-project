<?php

declare(strict_types=1);

namespace App\Services\AuthStrategies;

class BearerStrategy implements AuthStrategyInterface
{
    public function getHeaders(string $credentials): array
    {
        return [
            'Authorization' => "Bearer {$credentials}"
        ];
    }
}