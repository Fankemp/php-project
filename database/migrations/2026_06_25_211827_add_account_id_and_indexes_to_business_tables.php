<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Orders
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'odid'], 'idx_unq_acc_odid');
        });

        // 2. Sales
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'sale_id'], 'idx_unq_acc_saleid');
        });

        // 3. Incomes
        Schema::table('incomes', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'income_id', 'barcode'], 'idx_unq_acc_inc_bar');
        });

        // 4. Stocks
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->cascadeOnDelete();
            $table->unique(['account_id', 'warehouse_name', 'barcode'], 'idx_unq_acc_wh_bar');
        });
    }

    public function down(): void
    {
        $tables = ['orders', 'sales', 'incomes', 'stocks'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // При удалении колонки account_id, все индексы и внешние ключи, связанные с ней, тоже удалятся
                $table->dropForeign(['account_id']);
                $table->dropColumn('account_id');
            });
        }
    }
};