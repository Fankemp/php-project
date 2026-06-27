<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Account;
use App\Models\ApiService;
use App\Models\TokenType;
use App\Models\Token;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateEntityCommand extends Command
{
    // Команда для запуска: php artisan manage:create
    protected $signature = 'manage:create';
    protected $description = 'Интерактивное создание сущностей (Компании, Аккаунты, Сервисы, Типы, Токены) по ТЗ';

    public function handle(): int
    {
        $this->info("🛠️ Добро пожаловать в менеджер сущностей!");
        
        $choice = $this->choice(
            'Что вы хотите создать?',
            [
                1 => 'Компания (Company)',
                2 => 'Аккаунт (Account)',
                3 => 'API Сервис (ApiService)',
                4 => 'Тип токена (TokenType)',
                5 => 'API Токен (Token)'
            ]
        );

        switch ($choice) {
            case 'Компания (Company)':
                $this->createCompany();
                break;
            case 'Аккаунт (Account)':
                $this->createAccount();
                break;
            case 'API Сервис (ApiService)':
                $this->createApiService();
                break;
            case 'Тип токена (TokenType)':
                $this->createTokenType();
                break;
            case 'API Токен (Token)':
                $this->createApiToken();
                break;
        }

        return self::SUCCESS;
    }

    private function createCompany(): void
    {
        $name = $this->ask('Введите название новой Компании (например: ООО Рога и Копыта)');
        if (!$name) { $this->error('Название не может быть пустым!'); return; }

        $company = Company::create(['name' => $name]);
        $this->info("✅ Компания успешно создана! ID: {$company->id}");
    }

    private function createAccount(): void
    {
        $companies = Company::all();
        if ($companies->isEmpty()) { $this->error('❌ Сначала создайте хотя бы одну Компанию!'); return; }

        $companyMap = $companies->pluck('name', 'id')->toArray();
        $companyId = $this->choice('Выберите Компанию для этого аккаунта:', $companyMap);
        $companyId = array_search($companyId, $companyMap);

        $name = $this->ask('Введите имя аккаунта (например: Wildberries Основной)');
        if (!$name) { $this->error('Имя не может быть пустым!'); return; }

        $account = Account::create(['company_id' => $companyId, 'name' => $name]);
        $this->info("✅ Аккаунт создан! ID: {$account->id}, Привязан к Компании ID: {$companyId}");
    }

    private function createApiService(): void
    {
        $name = $this->ask('Введите название API сервиса (например: Ozon, Wildberries)');
        $url = $this->ask('Введите базовый URL API', 'https://statistics-api.wildberries.ru');

        if (!$name || !$url) { $this->error('Поля не могут быть пустыми!'); return; }

        $service = ApiService::create(['name' => $name, 'base_url' => $url]);
        $this->info("✅ API Сервис создан! ID: {$service->id}");
    }

    private function createTokenType(): void
    {
        $name = $this->ask('Введите понятное название типа (например: Bearer Токен, Ключ API)');
        $code = $this->ask('Введите системный код типа (например: bearer, api_key, login_password)');

        if (!$name || !$code) { $this->error('Поля не могут быть пустыми!'); return; }

        $type = TokenType::create(['name' => $name, 'code' => $code]);
        $this->info("✅ Тип токена создан! ID: {$type->id}");
    }

    private function createApiToken(): void
    {
        $accounts = Account::pluck('name', 'id')->toArray();
        $services = ApiService::pluck('name', 'id')->toArray();
        $types = TokenType::pluck('name', 'id')->toArray();

        if (empty($accounts) || empty($services) || empty($types)) {
            $this->error('❌ Для создания токена в базе должны быть Аккаунты, Сервисы и Типы токенов!');
            return;
        }

        $accChoice = $this->choice('Выберите Аккаунт:', $accounts);
        $accId = array_search($accChoice, $accounts);

        $srvChoice = $this->choice('Выберите API Сервис:', $services);
        $srvId = array_search($srvChoice, $services);

        $typeChoice = $this->choice('Выберите Тип токена:', $types);
        $typeId = array_search($typeChoice, $types);

        $credentials = $this->ask('Введите сам Токен (строку credentials/ключ)');
        if (!$credentials) { $this->error('Ключ не может быть пустым!'); return; }

        // По ТЗ: "у каждого апи сервиса свой набор типов токенов" -> привязываем в пивот
        DB::table('api_service_token_type')->insertOrIgnore([
            'api_service_id' => $srvId,
            'token_type_id' => $typeId
        ]);

        try {
            // По ТЗ уникальность: один тип токена для одного сервиса на один аккаунт
            $token = Token::create([
                'account_id' => $accId,
                'api_service_id' => $srvId,
                'token_type_id' => $typeId,
                'credentials' => $credentials,
                'is_active' => true
            ]);
            $this->info("✅ Токен успешно привязан! ID: {$token->id}");
        } catch (\Throwable $e) {
            $this->error("❌ Ошибка уникальности ТЗ: У этого аккаунта уже есть токен данного типа для этого сервиса!");
        }
    }
}