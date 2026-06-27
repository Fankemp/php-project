<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Token;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyncService
{
    /**
     * 1. Синхронизация Заказов
     */
    public function syncOrders(Token $token): void
    {
        $apiClient = new ApiClientService($token);
        $lastDate = Order::max('date'); 
        $dateFrom = $lastDate ? Carbon::parse($lastDate)->format('Y-m-d') : '2024-01-01';

        $data = $apiClient->fetchAllPaginated('orders', ['dateFrom' => $dateFrom]);
        $this->saveDataInChunks(Order::class, $data, ['odid']);
    }

    /**
     * 2. Синхронизация Продаж
     */
    public function syncSales(Token $token): void
    {
        $apiClient = new ApiClientService($token);
        $lastDate = Sale::max('date'); 
        $dateFrom = $lastDate ? Carbon::parse($lastDate)->format('Y-m-d') : '2024-01-01';

        $data = $apiClient->fetchAllPaginated('sales', ['dateFrom' => $dateFrom]);
        $this->saveDataInChunks(Sale::class, $data, ['sale_id']);
    }

    /**
     * 3. Синхронизация Поставок (Incomes)
     */
    public function syncIncomes(Token $token): void
    {
        $apiClient = new ApiClientService($token);
        $lastDate = Income::max('date'); 
        $dateFrom = $lastDate ? Carbon::parse($lastDate)->format('Y-m-d') : '2024-01-01';

        $data = $apiClient->fetchAllPaginated('incomes', ['dateFrom' => $dateFrom]);
        $this->saveDataInChunks(Income::class, $data, ['income_id', 'barcode']);
    }

    /**
     * 4. Синхронизация Складов (Остатки)
     */
    public function syncStocks(Token $token): void
    {
        $apiClient = new ApiClientService($token);
        // Склады обычно отдают актуальный срез на текущий день, API часто принимает просто dateFrom
        $dateFrom = Carbon::now()->format('Y-m-d'); 

        $data = $apiClient->fetchAllPaginated('stocks', ['dateFrom' => $dateFrom]);
        $this->saveDataInChunks(Stock::class, $data, ['warehouse_name', 'barcode']);
    }

    /**
     * Универсальный метод для безопасного сохранения данных пачками
     *
     * @param string $modelClass FQCN модели (например, Order::class)
     * @param array $data Массив данных от API
     * @param array $uniqueKeys Массив ключей для поиска (например, ['odid'])
     */
    private function saveDataInChunks(string $modelClass, array $data, array $uniqueKeys): void
    {
        if (empty($data)) {
            return;
        }

        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($modelClass, $chunk, $uniqueKeys) {
                foreach ($chunk as $item) {
                    // Формируем массив условий поиска для updateOrCreate
                    $attributes = [];
                    foreach ($uniqueKeys as $key) {
                        // Защита от отсутствующих ключей в ответе API (например, старый API отдавал g_number вместо odid)
                        if ($key === 'odid' && !isset($item['odid'])) {
                            $attributes['odid'] = $item['g_number'] ?? null;
                        } else {
                            $attributes[$key] = $item[$key] ?? null;
                        }
                    }

                    // Сохраняем или обновляем. Account_id подставится автоматически через TenantScope!
                    $modelClass::updateOrCreate($attributes, $item);
                }
            });
        }
    }
}