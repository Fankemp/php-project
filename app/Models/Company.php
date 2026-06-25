<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /**
     * Атрибуты, разрешенные для массового присвоения.
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Связь: Компания имеет много аккаунтов.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}