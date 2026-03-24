<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('partners', function (Blueprint $table) {
        $table->string('email')->nullable();
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('vat_number')->nullable();
        $table->string('contact_person')->nullable();
    });
}

public function down()
{
    Schema::table('partners', function (Blueprint $table) {
        $table->dropColumn(['email', 'address', 'city', 'vat_number', 'contact_person']);
    });
}
};
