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
        Schema::table('staff_promotions', function (Blueprint $table) {
            $table->integer('arrears_months')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_promotions', function (Blueprint $table) {
            $table->dropColumn('arrears_months');
        });
    }
};
