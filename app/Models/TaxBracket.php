<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxBracket extends Model
{
    use HasFactory;

    protected $fillable = [
        'version_name',
        'effective_date',
        'is_active',
        'tax_brackets',
        'reliefs',
        'description'
    ];

    protected $casts = [
        'tax_brackets' => 'array',
        'reliefs' => 'array',
        'effective_date' => 'date',
        'is_active' => 'boolean'
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query, $date = null)
    {
        $date = $date ?: now();
        return $query->where('effective_date', '<=', $date)
                    ->orderBy('effective_date', 'desc');
    }

    // Calculate tax for given taxable income
    public function calculateTax($taxableIncome)
    {
        $tax = 0;
        $remainingIncome = $taxableIncome;

        foreach ($this->tax_brackets as $bracket) {
            $min = $bracket['min'] ?? 0;
            $max = $bracket['max'] ?? null;
            $rate = $bracket['rate'] ?? 0;

            if ($remainingIncome <= 0) break;

            if ($max === null || $remainingIncome <= $max) {
                // Last bracket or income fits in this bracket
                $taxableInThisBracket = $remainingIncome;
            } else {
                // Income exceeds this bracket
                $taxableInThisBracket = $max - $min;
            }

            $tax += ($taxableInThisBracket * $rate / 100);
            $remainingIncome -= $taxableInThisBracket;
        }

        return round($tax, 2);
    }

    // Get total reliefs for an employee
    public function getTotalReliefs($basicSalary = 100000, $housingAllowance = 0, $transportAllowance = 0, $annualGross = null)
    {
        $defaultReliefs = [
            'consolidated_rent_relief' => ['fixed' => 200000, 'description' => 'Fixed consolidated rent relief allowance'],
            'pension_contribution' => ['percentage' => 8.0, 'base' => 'basic_housing_transport', 'description' => '8% of basic + housing + transport'],
            'nhf_contribution' => ['percentage' => 2.5, 'base' => 'basic', 'description' => '2.5% of basic salary'],
            'nhis_contribution' => ['percentage' => 0.5, 'base' => 'basic', 'description' => '0.5% of basic salary'],
        ];

        // Merge with saved reliefs, using defaults if not specified
        $reliefs = array_merge($defaultReliefs, $this->reliefs ?? []);

        $total = 0;
        $calculatedReliefs = [];

        foreach ($reliefs as $key => $relief) {
            if (isset($relief['fixed'])) {
                $amount = $relief['fixed'];
                // Add percentage of gross if specified
                if (isset($relief['percentage_of_gross']) && $annualGross) {
                    $grossAmount = ($relief['percentage_of_gross'] / 100) * $annualGross;
                    $amount += round($grossAmount, 2);
                }
                $calculatedReliefs[$key] = array_merge($relief, ['calculated_amount' => $amount]);
                $total += $amount;
            } elseif (isset($relief['percentage'])) {
                $percentage = $relief['percentage'];
                $base = $relief['base'] ?? 'basic';

                // Calculate base amount
                switch ($base) {
                    case 'basic_housing_transport':
                        $baseAmount = $basicSalary + $housingAllowance + $transportAllowance;
                        break;
                    case 'basic':
                    default:
                        $baseAmount = $basicSalary;
                        break;
                }

                $amount = ($percentage / 100) * $baseAmount;
                $calculatedReliefs[$key] = array_merge($relief, ['calculated_amount' => round($amount, 2)]);
                $total += round($amount, 2);
            }
        }

        return [
            'reliefs' => $calculatedReliefs,
            'total' => round($total, 2),
            'breakdown' => [
                'basic_salary' => $basicSalary,
                'housing_allowance' => $housingAllowance,
                'transport_allowance' => $transportAllowance,
                'annual_gross' => $annualGross,
            ]
        ];
    }

    // Get bracket summary for display
    public function getBracketSummary()
    {
        $summary = [];
        if ($this->tax_brackets) {
            foreach ($this->tax_brackets as $bracket) {
                $min = number_format($bracket['min']);
                $max = isset($bracket['max']) ? number_format($bracket['max']) : '∞';
                $rate = $bracket['rate'] . '%';
                $summary[] = "₦{$min}-₦{$max} @ {$rate}";
            }
        }
        return $summary;
    }

    /**
     * Calculate PAYE using this tax bracket configuration
     * Supports both standard and benchmark income methods
     */
    public function calculatePAYE($basicSalary, $taxableAllowances = 0, $statutoryBase = 'basic')
    {
        // Get configuration
        $config = $this->reliefs ?? [];
        $calculationMethod = $config['calculation_method'] ?? 'standard';
        $benchmarkDivisor = $config['benchmark_divisor'] ?? 2;
        
        // Calculate monthly gross
        $monthlyGross = $basicSalary + $taxableAllowances;
        
        // Calculate statutory deductions for relief
        $statutoryDeductions = $this->calculateStatutoryDeductions(
            $basicSalary, 
            $monthlyGross, 
            $statutoryBase
        );
        
        // Calculate annual gross based on method
        if ($calculationMethod === 'benchmark_income') {
            // Formula 2 style: Use Benchmark Income
            $netPay = $monthlyGross - $statutoryDeductions['total'];
            $benchmarkIncome = $netPay / $benchmarkDivisor;
            $annualGross = round($benchmarkIncome * 12, 2);
        } else {
            // Standard method (Formula 1 style)
            $annualBasic = round($basicSalary * 12, 2);
            $annualAllowances = round($taxableAllowances * 12, 2);
            $annualGross = round($annualBasic + $annualAllowances, 2);
        }
        
        // Calculate reliefs
        $reliefData = $this->calculateReliefs(
            $basicSalary,
            $monthlyGross,
            $annualGross,
            $statutoryBase
        );
        
        $totalRelief = $reliefData['total'];
        
        // Calculate taxable income
        $taxableIncome = max(0, round($annualGross - $totalRelief, 2));
        
        // Calculate annual tax using brackets
        $annualTax = $this->calculateTax($taxableIncome);
        
        // Calculate monthly PAYE
        $monthlyPAYE = round($annualTax / 12, 2);
        
        return $monthlyPAYE;
    }
    
    /**
     * Calculate statutory deductions (Pension, NHF, NHIS)
     */
    protected function calculateStatutoryDeductions($basicSalary, $monthlyGross, $base = 'basic')
    {
        $config = $this->reliefs ?? [];
        
        // Determine base amount
        $baseAmount = ($base === 'gross') ? $monthlyGross : $basicSalary;
        
        // Get percentages from config or use defaults
        $pensionRate = $config['pension']['percentage'] ?? 8.0;
        $nhfRate = $config['nhf']['percentage'] ?? 2.5;
        $nhisRate = $config['nhis']['percentage'] ?? 0.5;
        
        $pension = round(($pensionRate / 100) * $baseAmount, 2);
        $nhf = round(($nhfRate / 100) * $baseAmount, 2);
        $nhis = round(($nhisRate / 100) * $baseAmount, 2);
        
        return [
            'pension' => $pension,
            'nhf' => $nhf,
            'nhis' => $nhis,
            'total' => $pension + $nhf + $nhis,
            'base' => $base,
            'base_amount' => $baseAmount
        ];
    }
    
    /**
     * Calculate all reliefs based on configuration
     */
    protected function calculateReliefs($basicSalary, $monthlyGross, $annualGross, $statutoryBase = 'basic')
    {
        $config = $this->reliefs ?? [];
        $reliefs = [];
        $total = 0;
        
        // Consolidated Relief Allowance (CRA)
        if (isset($config['consolidated_relief'])) {
            $fixed = $config['consolidated_relief']['fixed'] ?? 200000;
            $percentageOfGross = $config['consolidated_relief']['percentage_of_gross'] ?? 20;
            
            $cra = $fixed + (($percentageOfGross / 100) * $annualGross);
            $reliefs['consolidated_relief'] = round($cra, 2);
            $total += $reliefs['consolidated_relief'];
        }
        
        // Pension Relief (Annual)
        if (isset($config['pension'])) {
            $rate = $config['pension']['percentage'] ?? 8.0;
            $base = $config['pension']['base'] ?? $statutoryBase;
            $baseAmount = ($base === 'gross') ? $annualGross : ($basicSalary * 12);
            
            $pension = ($rate / 100) * $baseAmount;
            $reliefs['pension'] = round($pension, 2);
            $total += $reliefs['pension'];
        }
        
        // NHF Relief (Annual)
        if (isset($config['nhf'])) {
            $rate = $config['nhf']['percentage'] ?? 2.5;
            $base = $config['nhf']['base'] ?? $statutoryBase;
            $baseAmount = ($base === 'gross') ? $annualGross : ($basicSalary * 12);
            
            $nhf = ($rate / 100) * $baseAmount;
            $reliefs['nhf'] = round($nhf, 2);
            $total += $reliefs['nhf'];
        }
        
        // NHIS Relief (Annual)
        if (isset($config['nhis'])) {
            $rate = $config['nhis']['percentage'] ?? 0.05;
            $base = $config['nhis']['base'] ?? $statutoryBase;
            $baseAmount = ($base === 'gross') ? $annualGross : ($basicSalary * 12);
            
            $nhis = ($rate / 100) * $baseAmount;
            $reliefs['nhis'] = round($nhis, 2);
            $total += $reliefs['nhis'];
        }
        
        return [
            'breakdown' => $reliefs,
            'total' => round($total, 2)
        ];
    }

    // Boot method to ensure only one active bracket
    protected static function booted()
    {
        static::saving(function ($bracket) {
            if ($bracket->is_active) {
                // Deactivate all other brackets
                static::where('id', '!=', $bracket->id)->update(['is_active' => false]);
            }
        });
    }
}
