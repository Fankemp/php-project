<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Реестр API сервисов
        Schema::create('api_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->timestamps();
        });

        // Реестр типов токенов
        Schema::create('token_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        // ПИВОТ: Какие типы токенов разрешены для какого сервиса (Требование ТЗ!)
        Schema::create('api_service_token_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_service_id')->constrained('api_services')->cascadeOnDelete();
            $table->foreignId('token_type_id')->constrained('token_types')->cascadeOnDelete();
            $table->unique(['api_service_id', 'token_type_id']); // Защита от дублирования связей
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_service_token_type');
        Schema::dropIfExists('token_types');
        Schema::dropIfExists('api_services');
    }
};