<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('api_service_id')->constrained('api_services')->cascadeOnDelete();
            $table->foreignId('token_type_id')->constrained('token_types')->cascadeOnDelete();
            $table->text('credentials'); // Сам ключ/токен или json с логином/паролем
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // ГАРАНТИЯ ТЗ: «у каждого аккаунта один токен одного типа для одного апи сервиса»
            $table->unique(['account_id', 'api_service_id', 'token_type_id'], 'user_token_unique_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};