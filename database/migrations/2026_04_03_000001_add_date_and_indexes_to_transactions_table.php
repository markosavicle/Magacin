<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('date')->nullable()->after('note');
        });

        // Backfill date from created_at for any existing rows
        DB::table('transactions')->whereNull('date')->update([
            'date' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
            $table->index(['partner_id', 'type']);
            $table->index(['partner_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['partner_id', 'type']);
            $table->dropIndex(['partner_id', 'date']);
            $table->dropColumn('date');
        });
    }
};
