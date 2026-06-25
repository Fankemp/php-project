<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TokenType;
use Illuminate\Console\Command;

class MakeTokenTypeCommand extends Command
{
    protected $signature = 'app:make-token-type';
    protected $description = 'Создает новый тип токена (Bearer, API Key)';

    public function handle(): int
    {
        $this->info('=== Создание Типа Токена ===');

        $code = $this->ask('Введите системный код (например: bearer, api_key, basic)');
        $name = $this->ask('Введите понятное название (например: Bearer Token)');

        if (empty($code) || empty($name)) {
            $this->error('Все поля обязательны для заполнения!');
            return self::FAILURE;
        }

        $type = TokenType::create([
            'code' => strtolower($code),
            'name' => $name,
        ]);

        $this->info(sprintf('Тип токена "%s" успешно создан! (ID: %d)', $type->name, $type->id));

        return self::SUCCESS;
    }
}