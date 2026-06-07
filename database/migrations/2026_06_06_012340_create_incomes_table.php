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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            // Основные идентификаторы
            $table->bigInteger('income_id')->nullable();
            $table->string('number')->nullable();
            $table->bigInteger('nm_id')->nullable();
            
            // Даты
            $table->date('date')->nullable();
            $table->date('last_change_date')->nullable();
            $table->date('date_close')->nullable();
            
            // Данные
            $table->string('supplier_article')->nullable();
            $table->string('tech_size')->nullable();
            $table->string('barcode')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('warehouse_name')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
