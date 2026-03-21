<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPostingBatchItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_posting_batch_id',
        'employee_id',
    ];

    public function batch()
    {
        return $this->belongsTo(SalaryPostingBatch::class, 'salary_posting_batch_id');
    }

    public function employee()
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }
}
