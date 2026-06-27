<?php

declare(strict_types=1);

namespace App\Services\AuthStrategies;

class ApiKeyStrategy implements AuthStrategyInterface
{
    public function getHeaders(string $credentials): array
    {
        return [
            'Authorization' => $credentials
        ];
    }
}