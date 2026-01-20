<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_id')->unique();

            $table->unsignedBigInteger('payment_import_id')->index();
            $table->foreign('payment_import_id')->references('id')->on('payment_imports')->cascadeOnDelete();

            $table->unsignedBigInteger('row_number')->nullable()->index();

            $table->string('customer_id', 64)->index();
            $table->string('customer_name', 255);
            $table->string('customer_email', 255)->index();

            $table->string('reference_no', 64)->index();

            $table->decimal('original_amount', 19, 4);
            $table->char('currency', 3)->index();

            $table->decimal('usd_amount', 19, 4);
            $table->decimal('exchange_rate', 19, 4);

            $table->dateTime('paid_at')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
