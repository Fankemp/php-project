<?php

declare(strict_types=1);

namespace App\Services\AuthStrategies;

interface AuthStrategyInterface
{
    /**
     * Возвращает массив заголовков, необходимых для авторизации
     */
    public function getHeaders(string $credentials): array;
}