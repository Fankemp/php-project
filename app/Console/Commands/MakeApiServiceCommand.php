<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ApiService;
use Illuminate\Console\Command;

class MakeApiServiceCommand extends Command
{
    protected $signature = 'app:make-api-service';
    protected $description = 'Создает новый API сервис (например, Wildberries, Ozon)';

    public function handle(): int
    {
        $this->info('=== Создание API Сервиса ===');

        $name = $this->ask('Введите название сервиса (например, Wildberries)');
        if (empty($name)) {
            $this->error('Название не может быть пустым!');
            return self::FAILURE;
        }

        $baseUrl = $this->ask('Введите базовый URL (опционально)');

        $service = ApiService::create([
            'name' => $name,
            'base_url' => $baseUrl,
        ]);

        $this->info(sprintf('API сервис "%s" успешно создан! (ID: %d)', $service->name, $service->id));

        return self::SUCCESS;
    }
}