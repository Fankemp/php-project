<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncServiceTest extends TestCase
{
    // Эта строчка будет очищать базу перед тестом, чтобы данные были чистыми
    use RefreshDatabase;

    public function test_it_successfully_maps_api_data_to_our_database()
    {
        // 1. ПОДГОТОВКА (Создаем тестовое окружение в БД)
        $company = Company::create(['name' => 'Тестовая Компания']);
        $account = Account::create(['company_id' => $company->id, 'name' => 'WB Аккаунт']);
        $service = ApiService::create(['name' => 'Wildberries', 'base_url' => 'https://test-api.com']);
        $type = TokenType::create(['name' => 'API Key', 'code' => 'api_key']);
        
        Token::create([
            'account_id' => $account->id,
            'api_service_id' => $service->id,
            'token_type_id' => $type->id,
            'credentials' => 'fake-secret-key',
            'is_active' => true
        ]);

        // 2. ИМИТАЦИЯ API (Mocking)
        // Мы говорим Laravel: "Если кто-то обратится по адресу /api/sales, не ходи в интернет, а верни этот JSON"
        Http::fake([
            'https://test-api.com/api/sales*' => Http::response([
                [
                    'sale_id' => 'S-999',
                    'date' => '2026-06-25',
                    'price' => 1500
                ]
            ], 200)
        ]);

        // 3. ДЕЙСТВИЕ (Запускаем твою консольную команду синхронизации)
        $this->artisan('sync:accounts')->assertExitCode(0);

        // 4. ПРОВЕРКА (Asserts)
        // Самое главное: проверяем, что данные легли в нашу базу с правильным account_id!
        $this->assertDatabaseHas('sales', [
            'account_id' => $account->id, // Доказательство, что маппинг сработал!
            'sale_id' => 'S-999',
            'price' => 1500
        ]);
    }
}