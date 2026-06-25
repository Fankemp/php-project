<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scopes\TenantScope;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}
