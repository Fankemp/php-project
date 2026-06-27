<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // Основные идентификаторы
            $table->string('g_number'); // Оставляем строкой 
            $table->bigInteger('odid')->nullable(); // ID позиции
            $table->bigInteger('nm_id')->nullable(); // Артикул 
            $table->bigInteger('income_id')->nullable(); // ID поставки
            
            // Даты
            $table->dateTime('date')->nullable(); // Дата заказа
            $table->dateTime('last_change_date')->nullable(); // Дата изменения
            $table->dateTime('cancel_dt')->nullable(); // Дата отмены
            
            // Финансы
            $table->decimal('total_price', 10, 2)->nullable(); // Цена
            $table->integer('discount_percent')->nullable(); // Скидка
            
            // Информация о товаре
            $table->string('supplier_article')->nullable(); // Артикул продавца
            $table->string('tech_size')->nullable(); // Размер
            $table->string('barcode')->nullable(); // Штрихкод
            $table->string('subject')->nullable(); // Предмет
            $table->string('category')->nullable(); // Категория
            $table->string('brand')->nullable(); // Бренд
            
            // Логистика
            $table->string('warehouse_name')->nullable(); // Склад
            $table->string('oblast')->nullable(); // Область
            
            // Статусы
            $table->boolean('is_cancel')->default(false); // Отменен ли заказ
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
