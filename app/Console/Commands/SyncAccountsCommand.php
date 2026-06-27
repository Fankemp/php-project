<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Token;
use Illuminate\Support\Str;
use App\Services\TenantService;
use App\Services\SyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncAccountsCommand extends Command
{
    protected $signature = 'sync:accounts';
    protected $description = 'Запуск синхронизации данных для всех активных аккаунтов';

    public function __construct(private SyncService $syncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🚀 Начинаем процесс синхронизации аккаунтов...');

        $tokens = Token::with('account')->get();

        if ($tokens->isEmpty()) {
            $this->warn('В базе нет доступных токенов для синхронизации.');
            return self::SUCCESS;
        }

        foreach ($tokens as $token) {
            $maskedToken = Str::mask($token->credentials, '*', 10, -5);
            $this->info("    Токен: {$maskedToken}");
            $this->line("=================================================");
            $this->info("Обработка Аккаунта: {$token->account->name} (ID: {$token->account_id})");
            try {
                TenantService::setAccountId($token->account_id);

                $this->info("Синхронизация Orders...");
                $this->syncService->syncOrders($token);

                $this->info("Синхронизация Sales...");
                $this->syncService->syncSales($token);

                $this->info("Синхронизация Incomes...");
                $this->syncService->syncIncomes($token);

                $this->info("Синхронизация Stocks...");
                $this->syncService->syncStocks($token);

                $this->info("✅ Аккаунт {$token->account->name} успешно синхронизирован.");

            } catch (Throwable $e) {
                $this->error("❌ Ошибка при синхронизации аккаунта {$token->account_id}: " . $e->getMessage());
            }
        }

        $this->line("=================================================");
        $this->info('Синхронизация всех аккаунтов завершена.');

        return self::SUCCESS;
    }
}