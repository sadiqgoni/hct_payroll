<?php

namespace App\Livewire\Forms;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\EmploymentType;
use App\Models\SalaryHistory;
use App\Models\SalaryPostingBatch;
use App\Models\SalaryPostingBatchItem;
use App\Models\SalaryStructure;
use App\Models\StaffCategory;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class SalaryLedgerPosting extends Component
{
    use LivewireAlert;

    public $month, $year, $description, $ids;
    public $posting_scope = 'all';
    public $batch_name = '';
    public $search = '';
    public $employee_type, $staff_category, $unit, $department, $salary_structure, $grade_level_from, $grade_level_to;
    public $specific_employee_ids = [];
    public $types = [], $categories = [], $salary_structures = [], $units = [], $departments = [];

    public function getListeners()
    {
        return ['confirmed', 'dismissed'];
    }

    protected function rules()
    {
        return [
            'month' => 'required',
            'year' => 'required',
            'description' => 'required',
            'batch_name' => 'required_if:posting_scope,batch|max:255',
            'specific_employee_ids' => 'required_if:posting_scope,batch|array|min:1',
        ];
    }

    protected function messages()
    {
        return [
            'batch_name.required_if' => 'Batch name is required when posting selected staff.',
            'specific_employee_ids.required_if' => 'Please select at least one employee for batch posting.',
            'specific_employee_ids.min' => 'Please select at least one employee for batch posting.',
        ];
    }

    public function generateDescription()
    {
        if ($this->month || $this->year) {
            $institutionName = strtoupper(app_settings()->name ?? 'INSTITUTION');
            $month = $this->month ?: date('F');
            $year = $this->year ?: date('Y');
            $this->description = $institutionName . ' SALARY ' . strtoupper($month) . ' ' . $year;
        }
    }

    public function mount()
    {
        $this->year = date('Y');
        $this->month = date('F');
        $this->generateDescription();
    }

    public function updated($prop)
    {
        $this->validateOnly($prop);
    }

    public function updatedMonth()
    {
        $this->generateDescription();
    }

    public function updatedYear()
    {
        $this->generateDescription();
    }

    public function updatedUnit()
    {
        if ($this->unit != '') {
            $this->departments = Department::where('unit_id', $this->unit)->get();
        } else {
            $this->departments = [];
        }
    }

    public function selectAllEmployees(array $ids): void
    {
        $this->specific_employee_ids = array_values(array_unique(array_merge($this->specific_employee_ids, $ids)));
    }

    public function deselectAllEmployees(array $ids): void
    {
        $this->specific_employee_ids = array_values(array_diff($this->specific_employee_ids, $ids));
    }

    protected function employeeFilterQuery()
    {
        return \App\Models\EmployeeProfile::join('salary_updates', 'salary_updates.employee_id', 'employee_profiles.id')
            ->select([
                'employee_profiles.id',
                'employee_profiles.full_name',
                'employee_profiles.staff_number',
                'employee_profiles.payroll_number',
                'employee_profiles.grade_level',
                'employee_profiles.step',
                'employee_profiles.unit',
                'employee_profiles.department',
                'employee_profiles.employment_type',
                'employee_profiles.staff_category',
                'employee_profiles.salary_structure',
            ])
            ->where('employee_profiles.status', 1)
            ->when($this->search, function ($query) {
                $search = trim($this->search);
                return $query->where(function ($q) use ($search) {
                    $q->where('employee_profiles.full_name', 'like', "%$search%")
                        ->orWhere('employee_profiles.staff_number', 'like', "%$search%")
                        ->orWhere('employee_profiles.payroll_number', 'like', "%$search%");
                });
            })
            ->when($this->salary_structure, function ($query) {
                return $query->where('employee_profiles.salary_structure', $this->salary_structure);
            })
            ->when($this->employee_type, function ($query) {
                return $query->where('employee_profiles.employment_type', $this->employee_type);
            })
            ->when($this->staff_category, function ($query) {
                return $query->where('employee_profiles.staff_category', $this->staff_category);
            })
            ->when($this->unit, function ($query) {
                return $query->where('employee_profiles.unit', $this->unit);
            })
            ->when($this->department, function ($query) {
                return $query->where('employee_profiles.department', $this->department);
            })
            ->when($this->grade_level_from, function ($query) {
                $gradeTo = $this->grade_level_to ?: $this->grade_level_from;
                return $query->whereBetween('employee_profiles.grade_level', [$this->grade_level_from, $gradeTo]);
            });
    }

    protected function selectedEmployeeQuery()
    {
        $query = $this->employeeFilterQuery();

        if ($this->posting_scope === 'batch') {
            $query->whereIn('employee_profiles.id', $this->specific_employee_ids ?: [0]);
        }

        return $query;
    }

    protected function selectedEmployeesForPosting()
    {
        return \App\Models\EmployeeProfile::join('salary_updates', 'salary_updates.employee_id', 'employee_profiles.id')
            ->select([
                'employee_profiles.id as employee_id',
                'salary_updates.*',
                'employee_profiles.full_name',
                'employee_profiles.staff_number',
                'employee_profiles.payroll_number',
                'employee_profiles.department',
                'employee_profiles.staff_category',
                'employee_profiles.employment_type',
                'employee_profiles.phone_number',
                'employee_profiles.status',
                'employee_profiles.bank_code',
                'employee_profiles.account_number',
                'employee_profiles.bank_name',
                'employee_profiles.pfa_name',
                'employee_profiles.pension_pin',
                'employee_profiles.grade_level',
                'employee_profiles.unit',
                'employee_profiles.salary_structure',
                'employee_profiles.step',
            ])
            ->whereIn('employee_profiles.id', $this->selectedEmployeeQuery()->pluck('employee_profiles.id')->all() ?: [0])
            ->get();
    }

    protected function postingDate(): string
    {
        return Carbon::parse($this->month . '-' . $this->year)->format('Y-m-d');
    }

    protected function existingAllPostingExists(): bool
    {
        return SalaryHistory::where('salary_month', $this->month)
            ->where('salary_year', $this->year)
            ->exists();
    }

    protected function existingBatchConflicts(): bool
    {
        $selectedEmployees = $this->selectedEmployeeQuery()->get();

        if ($selectedEmployees->isEmpty()) {
            return false;
        }

        $employeeIds = $selectedEmployees->pluck('id')->filter()->all();
        $payrollNumbers = $selectedEmployees->pluck('payroll_number')->filter()->all();
        $staffNumbers = $selectedEmployees->pluck('staff_number')->filter()->all();

        $historyConflict = SalaryHistory::where('salary_month', $this->month)
            ->where('salary_year', $this->year)
            ->where(function ($query) use ($employeeIds, $payrollNumbers, $staffNumbers) {
                if (!empty($employeeIds)) {
                    $query->orWhereIn('employee_id', $employeeIds);
                }
                if (!empty($payrollNumbers)) {
                    $query->orWhereIn('ip_number', $payrollNumbers);
                }
                if (!empty($staffNumbers)) {
                    $query->orWhereIn('pf_number', $staffNumbers);
                }
            })
            ->exists();

        $sameBatchExists = SalaryPostingBatch::where('batch_name', $this->batch_name)
            ->where('salary_month', $this->month)
            ->where('salary_year', $this->year)
            ->exists();

        return $historyConflict || $sameBatchExists;
    }

    public function store()
    {
        $this->validate();

        if ($this->posting_scope === 'all') {
            if ($this->existingAllPostingExists()) {
                $this->alert('warning', 'Record exists for ' . $this->month . ' ' . $this->year . '. Do you want to overwrite all posted salaries for this month?', [
                    'showConfirmButton' => true,
                    'confirmButtonText' => 'Yes',
                    'onConfirmed' => 'confirmed',
                    'showCancelButton' => true,
                    'onDismissed' => 'cancelled',
                    'position' => 'center',
                    'timer' => 90000,
                    'toast' => true,
                ]);
                return;
            }
        } elseif ($this->existingBatchConflicts()) {
            $this->alert('warning', 'Some selected staff or this batch name already have posted salary for ' . $this->month . ' ' . $this->year . '. Do you want to overwrite this selected batch?', [
                'showConfirmButton' => true,
                'confirmButtonText' => 'Yes',
                'onConfirmed' => 'confirmed',
                'showCancelButton' => true,
                'onDismissed' => 'cancelled',
                'position' => 'center',
                'timer' => 90000,
                'toast' => true,
            ]);
            return;
        }

        $this->storeRecord(false);
    }

    public function confirmed()
    {
        $this->storeRecord(true);
    }

    protected function syncBatchRecord(): SalaryPostingBatch
    {
        $batch = SalaryPostingBatch::firstOrCreate(
            [
                'batch_name' => $this->batch_name,
                'salary_month' => $this->month,
                'salary_year' => $this->year,
            ],
            [
                'description' => $this->description,
                'selection_filters' => $this->selectionFilters(),
                'created_by' => Auth::id(),
            ]
        );

        $batch->description = $this->description;
        $batch->selection_filters = $this->selectionFilters();
        $batch->created_by = Auth::id();
        $batch->save();

        SalaryPostingBatchItem::where('salary_posting_batch_id', $batch->id)->delete();

        $items = collect($this->selectedEmployeeQuery()->pluck('employee_profiles.id')->all())
            ->unique()
            ->map(function ($employeeId) use ($batch) {
                return [
                    'salary_posting_batch_id' => $batch->id,
                    'employee_id' => $employeeId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->all();

        if (!empty($items)) {
            SalaryPostingBatchItem::insert($items);
        }

        return $batch;
    }

    protected function selectionFilters(): array
    {
        return [
            'search' => $this->search,
            'employee_type' => $this->employee_type,
            'staff_category' => $this->staff_category,
            'unit' => $this->unit,
            'department' => $this->department,
            'salary_structure' => $this->salary_structure,
            'grade_level_from' => $this->grade_level_from,
            'grade_level_to' => $this->grade_level_to,
        ];
    }

    protected function deleteExistingTargetHistory(?SalaryPostingBatch $batch, bool $overwrite): void
    {
        if ($this->posting_scope === 'all') {
            if ($overwrite) {
                SalaryHistory::where('salary_month', $this->month)
                    ->where('salary_year', $this->year)
                    ->delete();
            }
            return;
        }

        if ($batch && $overwrite) {
            SalaryHistory::where('salary_month', $this->month)
                ->where('salary_year', $this->year)
                ->where('salary_posting_batch_id', $batch->id)
                ->delete();
        }

        $selectedEmployees = $this->selectedEmployeeQuery()->get();
        $employeeIds = $selectedEmployees->pluck('id')->filter()->all();
        $payrollNumbers = $selectedEmployees->pluck('payroll_number')->filter()->all();
        $staffNumbers = $selectedEmployees->pluck('staff_number')->filter()->all();

        SalaryHistory::where('salary_month', $this->month)
            ->where('salary_year', $this->year)
            ->where(function ($query) use ($employeeIds, $payrollNumbers, $staffNumbers) {
                if (!empty($employeeIds)) {
                    $query->orWhereIn('employee_id', $employeeIds);
                }
                if (!empty($payrollNumbers)) {
                    $query->orWhereIn('ip_number', $payrollNumbers);
                }
                if (!empty($staffNumbers)) {
                    $query->orWhereIn('pf_number', $staffNumbers);
                }
            })
            ->delete();
    }

    protected function storeRecord(bool $overwrite): void
    {
        $this->validate();
        set_time_limit(2000);

        $employees = $this->posting_scope === 'all'
            ? \App\Models\EmployeeProfile::join('salary_updates', 'salary_updates.employee_id', 'employee_profiles.id')
                ->select([
                    'employee_profiles.id as employee_id',
                    'salary_updates.*',
                    'employee_profiles.full_name',
                    'employee_profiles.staff_number',
                    'employee_profiles.payroll_number',
                    'employee_profiles.department',
                    'employee_profiles.staff_category',
                    'employee_profiles.employment_type',
                    'employee_profiles.phone_number',
                    'employee_profiles.status',
                    'employee_profiles.bank_code',
                    'employee_profiles.account_number',
                    'employee_profiles.bank_name',
                    'employee_profiles.pfa_name',
                    'employee_profiles.pension_pin',
                    'employee_profiles.grade_level',
                    'employee_profiles.unit',
                    'employee_profiles.salary_structure',
                    'employee_profiles.step',
                ])
                ->where('employee_profiles.status', 1)
                ->get()
            : $this->selectedEmployeesForPosting();

        if ($employees->isEmpty()) {
            $this->alert('warning', 'No employees found for the selected posting scope.', ['timer' => 9000]);
            return;
        }

        $batch = null;
        if ($this->posting_scope === 'batch') {
            $batch = $this->syncBatchRecord();
        }

        DB::transaction(function () use ($employees, $batch, $overwrite) {
            $this->deleteExistingTargetHistory($batch, $overwrite);

            $dateMonth = $this->postingDate();
            foreach ($employees as $employee) {
                $salary = new SalaryHistory();
                $salary->employee_id = $employee->employee_id;
                $salary->salary_posting_batch_id = $batch?->id;
                $salary->salary_posting_batch_name = $batch?->batch_name;
                $salary->salary_month = $this->month;
                $salary->salary_year = $this->year;

                $salary->pf_number = $employee->staff_number;
                $salary->ip_number = $employee->payroll_number;
                $salary->full_name = $employee->full_name;
                $salary->department = dept($employee->department);
                $salary->staff_category = staff_cat($employee->staff_category);
                $salary->phone_number = $employee->phone_number;
                $salary->employment_type = emp_type($employee->employment_type);
                $salary->employment_status = emp_status($employee->status);
                $salary->salary_structure = ss($employee->salary_structure);
                $salary->grade_level = $employee->grade_level;
                $salary->step = $employee->step;
                $salary->unit = unit_name($employee->unit);
                $salary->bank_code = $employee->bank_code;
                $salary->account_number = $employee->account_number;
                $salary->bank_name = $employee->bank_name;
                $salary->pfa_name = $employee->pfa_name;
                $salary->pension_pin = $employee->pension_pin;
                $salary->basic_salary = round($employee->basic_salary, 2);
                $salary->A1 = $employee->A1;
                $salary->A2 = $employee->A2;
                $salary->A3 = $employee->A3;
                $salary->A4 = $employee->A4;
                $salary->A5 = $employee->A5;
                $salary->A6 = $employee->A6;
                $salary->A7 = $employee->A7;
                $salary->A8 = $employee->A8;
                $salary->A9 = $employee->A9;
                $salary->A10 = $employee->A10;
                $salary->A11 = $employee->A11;
                $salary->A12 = $employee->A12;
                $salary->A13 = $employee->A13;
                $salary->A14 = $employee->A14;
                $salary->D1 = $employee->D1;
                $salary->D2 = $employee->D2;
                $salary->D3 = $employee->D3;
                $salary->D4 = $employee->D4;
                $salary->D5 = $employee->D5;
                $salary->D6 = $employee->D6;
                $salary->D7 = $employee->D7;
                $salary->D8 = $employee->D8;
                $salary->D9 = $employee->D9;
                $salary->D10 = $employee->D10;
                $salary->D11 = $employee->D11;
                $salary->D12 = $employee->D12;
                $salary->D13 = $employee->D13;
                $salary->D14 = $employee->D14;
                $salary->D15 = $employee->D15;
                $salary->D16 = $employee->D16;
                $salary->D17 = $employee->D17;
                $salary->D18 = $employee->D18;
                $salary->D19 = $employee->D19;
                $salary->D20 = $employee->D20;
                $salary->D21 = $employee->D21;
                $salary->D22 = $employee->D22;
                $salary->D23 = $employee->D23;
                $salary->D24 = $employee->D24;
                $salary->D25 = $employee->D25;
                $salary->D26 = $employee->D26;
                $salary->D27 = $employee->D27;
                $salary->D28 = $employee->D28;
                $salary->D29 = $employee->D29;
                $salary->D30 = $employee->D30;
                $salary->D31 = $employee->D31;
                $salary->D32 = $employee->D32;
                $salary->D33 = $employee->D33;
                $salary->D34 = $employee->D34;
                $salary->D35 = $employee->D35;
                $salary->D36 = $employee->D36;
                $salary->D37 = $employee->D37;
                $salary->D38 = $employee->D38;
                $salary->D39 = $employee->D39;
                $salary->D40 = $employee->D40;
                $salary->D41 = $employee->D41;
                $salary->D42 = $employee->D42;
                $salary->D43 = $employee->D43;
                $salary->D44 = $employee->D44;
                $salary->D45 = $employee->D45;
                $salary->D46 = $employee->D46;
                $salary->D47 = $employee->D47;
                $salary->D48 = $employee->D48;
                $salary->D49 = $employee->D49;
                $salary->D50 = $employee->D50;
                $salary->salary_areas = $employee->salary_arears;
                $salary->gross_pay = $employee->gross_pay;
                $salary->total_deduction = $employee->total_deduction;
                $salary->total_allowance = $employee->total_allowance;
                $salary->net_pay = $employee->net_pay;
                $salary->deduction_countdown = $employee->deduction_countdown;
                $salary->nhis = $employee->nhis;
                $salary->employer_pension = $employee->employer_pension;
                $salary->salary_remark = $this->description;
                $salary->date_month = $dateMonth;
                $salary->save();
            }
        });

        $count = $employees->count();
        $scopeMessage = $this->posting_scope === 'batch'
            ? 'Batch "' . $this->batch_name . '" posted for ' . $count . ' staff.'
            : 'Salary posted for ' . $count . ' staff.';

        $this->alert('success', $scopeMessage, ['timer' => 7000]);

        $user = Auth::user();
        $log = new ActivityLog();
        $log->user_id = $user->id;
        $log->action = $this->posting_scope === 'batch'
            ? 'Posted salary batch ' . $this->batch_name . ' for ' . $this->month . ' ' . $this->year
            : 'Posted salary ledger for ' . $this->month . ' ' . $this->year;
        $log->save();
    }

    public function render()
    {
        $this->types = EmploymentType::all();
        $this->categories = StaffCategory::all();
        $this->salary_structures = SalaryStructure::where('status', 1)->get();
        $this->units = Unit::where('status', 1)->get();

        $specificCandidates = $this->employeeFilterQuery()
            ->orderBy('employee_profiles.grade_level')
            ->orderBy('employee_profiles.step')
            ->orderBy('employee_profiles.full_name')
            ->get()
            ->groupBy(function ($item) {
                return 'Grade Level ' . $item->grade_level . ' - Step ' . $item->step;
            });

        $recentSalaries = SalaryHistory::select(
            'salary_month',
            'salary_year',
            'salary_posting_batch_name',
            DB::raw('COUNT(*) as staff_count')
        )
            ->groupBy('salary_month', 'salary_year', 'salary_posting_batch_name')
            ->orderBy('salary_year', 'desc')
            ->orderByRaw("STR_TO_DATE(CONCAT('01 ', salary_month, ' ', salary_year), '%d %M %Y') DESC")
            ->limit(12)
            ->get();

        return view('livewire.forms.salary-ledger-posting', compact('recentSalaries', 'specificCandidates'))
            ->extends('components.layouts.app');
    }
}
