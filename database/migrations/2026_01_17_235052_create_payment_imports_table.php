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
        Schema::create('payment_imports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_id')->unique();

            $table->string('original_filename');
            $table->string('source_disk');
            $table->string('source_path');

            $table->string('status')->index();

            $table->unsignedBigInteger('total_rows')->nullable();
            $table->unsignedBigInteger('valid_rows')->default(0);
            $table->unsignedBigInteger('invalid_rows')->default(0);

            $table->unsignedInteger('chunk_count')->default(0);

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->json('meta')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_imports');
    }
};
