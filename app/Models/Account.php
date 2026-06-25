<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    /**
     * Атрибуты, разрешенные для массового присвоения.
     */
    protected $fillable = [
        'company_id',
        'name',
    ];

    /**
     * Связь: Аккаунт принадлежит одной компании.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}