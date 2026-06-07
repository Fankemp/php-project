<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            
            // Основные идентификаторы
            $table->bigInteger('nm_id')->nullable(); 
            $table->string('barcode')->nullable();
            $table->string('supplier_article')->nullable();
            
            // Логистика и склад
            $table->string('warehouse_name')->nullable(); // <-- Вот из-за чего была ошибка
            $table->string('tech_size')->nullable();
            
            // Количество и цены
            $table->integer('quantity')->nullable();
            $table->integer('quantity_full')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('discount')->nullable();
            
            // Даты
            $table->dateTime('date')->nullable();
            $table->dateTime('last_change_date')->nullable();
            
            // Инфо о товаре
            $table->string('subject')->nullable();
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->string('sc_code')->nullable();
            $table->boolean('is_supply')->nullable()->default(false);
            $table->boolean('is_realization')->nullable()->default(false);

            $table->integer('in_way_to_client')->nullable();   // В пути к клиенту
            $table->integer('in_way_from_client')->nullable(); // В пути от клиента
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
