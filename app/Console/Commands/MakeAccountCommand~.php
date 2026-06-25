<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Console\Command;

class MakeAccountCommand extends Command
{
    /**
     * Имя и сигнатура команды в консоли.
     */
    protected $signature = 'app:make-account';

    /**
     * Описание команды.
     */
    protected $description = 'Создает новый аккаунт и привязывает его к существующей компании';

    public function handle(): int
    {
        $this->info('=== Создание нового аккаунта ===');

        // 1. Проверяем, есть ли компании в базе данных
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->error('В базе нет ни одной компании! Сначала создайте компанию: php artisan app:make-company');
            return self::FAILURE;
        }

        // 2. Формируем массив для интерактивного выбора (ID => Имя)
        $choices = [];
        foreach ($companies as $company) {
            // Форматируем строку так, чтобы было понятно, что мы выбираем
            $choices[$company->id] = "ID: {$company->id} - {$company->name}";
        }

        // 3. Даем пользователю выбрать компанию из списка (с помощью стрелочек или ввода текста)
        $selectedCompanyString = $this->choice(
            'Выберите компанию, к которой будет привязан аккаунт',
            $choices
        );

        // Получаем обратно ID выбранной компании (находим ключ по значению)
        $companyId = array_search($selectedCompanyString, $choices);

        // 4. Запрашиваем название для нового аккаунта
        $name = $this->ask('Введите название аккаунта');

        if (empty($name)) {
            $this->error('Название аккаунта не может быть пустым!');
            return self::FAILURE;
        }

        // 5. Создаем аккаунт
        $account = Account::create([
            'company_id' => $companyId,
            'name' => $name,
        ]);

        $this->info(sprintf('Аккаунт "%s" успешно создан и привязан к компании (ID: %d)!', $account->name, $companyId));

        return self::SUCCESS;
    }
}