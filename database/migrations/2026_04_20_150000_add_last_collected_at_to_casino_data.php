<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casino_data', function (Blueprint $table) {
            $table->timestamp('last_collected_at')->nullable()->after('machines');
        });
    }

    public function down(): void
    {
        Schema::table('casino_data', function (Blueprint $table) {
            $table->dropColumn('last_collected_at');
        });
    }
};
