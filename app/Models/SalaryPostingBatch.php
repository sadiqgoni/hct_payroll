<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryPostingBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_name',
        'salary_month',
        'salary_year',
        'description',
        'selection_filters',
        'created_by',
    ];

    protected $casts = [
        'selection_filters' => 'array',
    ];

    public function items()
    {
        return $this->hasMany(SalaryPostingBatchItem::class);
    }
}
