<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\TenantScope;

class Income extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'amount',
        'source',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }   

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}
