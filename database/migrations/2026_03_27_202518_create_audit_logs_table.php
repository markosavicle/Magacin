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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('model_type'); // e.g., 'App\Models\Partner'
            $table->unsignedBigInteger('model_id'); // ID of the model
            $table->string('action'); // 'created', 'updated', 'deleted'
            $table->unsignedBigInteger('user_id')->nullable(); // Who made the change
            $table->json('old_values')->nullable(); // Old values before change
            $table->json('new_values')->nullable(); // New values after change
            $table->string('ip_address')->nullable(); // IP address of the user
            $table->text('description')->nullable(); // Human readable description
            $table->timestamps();

            $table->index(['model_type', 'model_id']);
            $table->index('user_id');
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
