<?php

declare(strict_types=1);

namespace App\Services;

class TenantService
{
    private static ?int $accountId = null;

    /**
     * Устанавливаем текущий аккаунт (будем вызывать перед началом парсинга)
     */
    public static function setAccountId(int $id): void
    {
        self::$accountId = $id;
    }

    /**
     * Получаем текущий аккаунт
     */
    public static function getAccountId(): ?int
    {
        return self::$accountId;
    }
}