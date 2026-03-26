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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('partners', 'id')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('date');
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('status')->default(0); // 0 - unpaid, 1 - paid
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
