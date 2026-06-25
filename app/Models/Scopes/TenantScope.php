<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Этот метод автоматически применяется ко всем запросам модели.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $accountId = TenantService::getAccountId();

        // Если аккаунт установлен, жестко фильтруем запросы по нему
        if ($accountId !== null) {
            $builder->where('account_id', $accountId);
        }
    }
}