<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    // Эта строчка разрешает нам сохранять в базу любые поля без ограничений
    protected $guarded = [];
}
