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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price')->default(500);
            $table->integer('loss_percentage'); // Plus c'est élevé, plus le gain potentiel est grand
            $table->integer('potential_gain');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('status', ['available', 'purchased', 'scratching', 'completed'])->default('available');
            $table->enum('result', ['win', 'lose'])->nullable();
            $table->integer('scratched_percentage')->default(0); // 0-100, révèle à 75%
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
