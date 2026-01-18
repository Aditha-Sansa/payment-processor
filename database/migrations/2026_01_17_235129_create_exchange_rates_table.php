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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('provider', 64);
            $table->char('base', 3);
            $table->date('date');

            $table->json('rates');
            $table->timestamp('fetched_at');

            $table->timestamps();

            $table->unique(['provider', 'base', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
