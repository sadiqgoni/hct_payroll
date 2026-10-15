<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staff_promotions', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_promotions', 'type')) {
                // 'promotion' = same salary structure, 'conversion' = moved to a new salary structure
                $table->string('type', 20)->default('promotion');
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_promotions', function (Blueprint $table) {
            if (Schema::hasColumn('staff_promotions', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
