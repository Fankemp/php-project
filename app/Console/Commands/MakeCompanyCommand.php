<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class MakeCompanyCommand extends Command
{
    /**
     * Имя и сигнатура команды в консоли.
     */
    protected $signature = 'app:make-company';

    /**
     * Описание команды.
     */
    protected $description = 'Создает новую компанию в системе';

    public function handle(): int
    {
        $this->info('=== Создание новой компании ===');

        // Запрашиваем имя у пользователя с базовой валидацией
        $name = $this->ask('Введите название компании');

        if (empty($name)) {
            $this->error('Название компании не может быть пустым!');
            return self::FAILURE;
        }

        // Создаем запись в БД
        $company = Company::create([
            'name' => $name,
        ]);

        $this->info(sprintf('Компания "%s" успешно создана! (ID: %d)', $company->name, $company->id));

        return self::SUCCESS;
    }
}