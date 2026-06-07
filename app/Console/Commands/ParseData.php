<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiService;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Income;
use App\Models\Stock;
use Illuminate\Support\Facades\DB; 
use Exception;

class ParseData extends Command
{
   protected $signature = 'api:parse {--dateFrom=2024-01-01} {--dateTo=} {--only=}';
    protected $description = 'Парсинг данных из API (С транзакциями БД)';

    private $apiService;

    public function __construct(ApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $this->info("🚀 Запуск парсера API...");

        $dateFrom = $this->option('dateFrom');
        $dateTo = $this->option('dateTo') ?: date('Y-m-d');
        $stockDate = date('Y-m-d');
        
        // 2. Читаем флаг из консоли
        $only = $this->option('only'); 

        try {
            // Если флага нет — качаем всё
            if (!$only) {
                $this->parseOrders($dateFrom, $dateTo);
                $this->parseSales($dateFrom, $dateTo);
                $this->parseIncomes($dateFrom, $dateTo);
                $this->parseStocks($stockDate);
            } 
            // Если флаг есть — запускаем только нужный метод
            else {
                $this->info("⚙️ Выбран точечный режим: только {$only}");
                
                if ($only === 'orders') {
                    $this->parseOrders($dateFrom, $dateTo);
                } elseif ($only === 'sales') {
                    $this->parseSales($dateFrom, $dateTo);
                } elseif ($only === 'incomes') {
                    $this->parseIncomes($dateFrom, $dateTo);
                } elseif ($only === 'stocks') {
                    $this->parseStocks($stockDate);
                } else {
                    $this->error("❌ Неизвестная таблица: {$only}. Доступно: orders, sales, incomes, stocks.");
                    return;
                }
            }

            $this->info("✅ Парсинг успешно завершен!");
        } catch (Exception $e) {
            $this->error("\n❌ Процесс прерван: " . $e->getMessage());
        }
    }

    private function parseOrders($dateFrom, $dateTo)
    {
        $this->warn("\n📦 Скачивание Заказов...");
        $data = $this->apiService->getEndpointData('orders', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        if (empty($data)) return $this->line("Нет данных.");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        // Разбиваем весь массив на пачки по 500 штук
        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            // Оборачиваем каждую пачку в транзакцию
            DB::transaction(function () use ($chunk, $bar) {
                foreach ($chunk as $item) {
                    Order::updateOrCreate(['g_number' => $item['g_number']], $item);
                    $bar->advance();
                }
            });
        }
        $bar->finish();
    }

    private function parseSales($dateFrom, $dateTo)
    {
        $this->warn("\n💰 Скачивание Продаж...");
        $data = $this->apiService->getEndpointData('sales', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        if (empty($data)) return $this->line("Нет данных.");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, $bar) {
                foreach ($chunk as $item) {
                    Sale::updateOrCreate(['sale_id' => $item['sale_id']], $item);
                    $bar->advance();
                }
            });
        }
        $bar->finish();
    }

    private function parseIncomes($dateFrom, $dateTo)
    {
        $this->warn("\n📥 Скачивание Доходов...");
        $data = $this->apiService->getEndpointData('incomes', ['dateFrom' => $dateFrom, 'dateTo' => $dateTo]);

        if (empty($data)) return $this->line("Нет данных.");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, $bar) {
                foreach ($chunk as $item) {
                    Income::updateOrCreate(['income_id' => $item['income_id'], 'barcode' => $item['barcode']], $item);
                    $bar->advance();
                }
            });
        }
        $bar->finish();
    }

    private function parseStocks($dateFrom)
    {
        $this->warn("\n🏢 Скачивание Складов...");
        $data = $this->apiService->getEndpointData('stocks', ['dateFrom' => $dateFrom]);

        if (empty($data)) return $this->line("Нет данных.");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        $chunks = array_chunk($data, 500);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk, $bar) {
                foreach ($chunk as $item) {
                    Stock::updateOrCreate(
                        ['nm_id' => $item['nm_id'], 'warehouse_name' => $item['warehouse_name'], 'date' => $item['date']], 
                        $item
                    );
                    $bar->advance();
                }
            });
        }
        $bar->finish();
    }
}