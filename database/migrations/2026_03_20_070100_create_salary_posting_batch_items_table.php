<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('salary_posting_batch_items')) {
            Schema::create('salary_posting_batch_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('salary_posting_batch_id');
                $table->unsignedBigInteger('employee_id');
                $table->timestamps();

                $table->unique(['salary_posting_batch_id', 'employee_id'], 'salary_posting_batch_items_unique');
                $table->index('employee_id', 'salary_posting_batch_items_employee_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_posting_batch_items');
    }
};
