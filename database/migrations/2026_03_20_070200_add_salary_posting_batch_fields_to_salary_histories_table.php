<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('salary_histories')) {
            Schema::table('salary_histories', function (Blueprint $table) {
                if (!Schema::hasColumn('salary_histories', 'employee_id')) {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                }
                if (!Schema::hasColumn('salary_histories', 'salary_posting_batch_id')) {
                    $table->unsignedBigInteger('salary_posting_batch_id')->nullable()->after('employee_id');
                }
                if (!Schema::hasColumn('salary_histories', 'salary_posting_batch_name')) {
                    $table->string('salary_posting_batch_name')->nullable()->after('salary_posting_batch_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('salary_histories')) {
            Schema::table('salary_histories', function (Blueprint $table) {
                if (Schema::hasColumn('salary_histories', 'salary_posting_batch_name')) {
                    $table->dropColumn('salary_posting_batch_name');
                }
                if (Schema::hasColumn('salary_histories', 'salary_posting_batch_id')) {
                    $table->dropColumn('salary_posting_batch_id');
                }
                if (Schema::hasColumn('salary_histories', 'employee_id')) {
                    $table->dropColumn('employee_id');
                }
            });
        }
    }
};
