<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('salary_posting_batches')) {
            Schema::create('salary_posting_batches', function (Blueprint $table) {
                $table->id();
                $table->string('batch_name');
                $table->string('salary_month');
                $table->string('salary_year');
                $table->string('description')->nullable();
                $table->json('selection_filters')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['salary_year', 'salary_month'], 'salary_posting_batches_period_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_posting_batches');
    }
};
