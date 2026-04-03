<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->index(['partner_id', 'type']);
            $table->index(['partner_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['partner_id', 'type']);
            $table->dropIndex(['partner_id', 'date']);
            $table->tinyInteger('status')->default(0)->after('amount');
        });
    }
};
