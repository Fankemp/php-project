<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TokenType extends Model
{
    protected $fillable = [
        'code',
        'name',
    ];

    public function tokens(): HasMany
    {
        return $this->hasMany(Token::class);
    }

    public function apiServices(): BelongsToMany
    {
        return $this->belongsToMany(ApiService::class, 'api_service_token_type');
    }
}