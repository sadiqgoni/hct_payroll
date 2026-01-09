<table id="header">
    <tr style="padding: 0;margin: 0;text-align: center;text-transform: uppercase"><td colspan="20" style="text-align: center">{{app_settings()->name}}</td></tr>
    <tr style="padding: 0;margin: 0;text-align: center"><td colspan="20" style="text-align: center">{{address()}}</td></tr>
    <tr style="position: absolute;right:50px;margin-top: 20px;margin-bottom: 0px " ><td colspan="20" style="text-align: center">Month: {{\Illuminate\Support\Carbon::parse($date_from)->format('F Y')}}</td></tr>
    <tr style="text-align: center !important;margin: 10px 0;text-transform: uppercase;"><td colspan="20" style="text-align: center">Salary Deduction Schedule</td></tr>
</table>

{{-- Continuous deduction schedule - all staff with their deductions in columns --}}
@php
    $deductions = \App\Models\Deduction::where('status', 1)->orderBy('id')->get();
    $allStaff = collect();

    // Collect all unique staff members
    foreach($reports as $staffDeductions) {
        if($staffDeductions->count() > 0) {
            $staff = $staffDeductions->first();
            $allStaff->push([
                'staff_number' => $staff->staff_number,
                'staff_name' => $staff->staff_name,
                'deductions' => $staffDeductions
            ]);
        }
    }
@endphp

<table border="1" style="width:100%;border-collapse: collapse;font-size: 12px;" >
    <thead>
    <tr>
        <th>S/N</th>
        <th>Staff No</th>
        <th>Payroll No</th>
        <th>Staff Name</th>
        @foreach($deductions as $deduction)
            <th>{{ $deduction->deduction_name }}</th>
        @endforeach
        <th>Total Deductions</th>
    </tr>
    </thead>
    <tbody>
    @php $counter = 1; @endphp
    @forelse($allStaff as $staff)
        @php
            $emp = \App\Models\EmployeeProfile::where('staff_number', $staff['staff_number'])->first();
            $totalDeductions = 0;
        @endphp
        <tr style="text-align: center">
            <td>{{ $counter++ }}</td>
            <td>{{ $staff['staff_number'] }}</td>
            <td>{{ $emp->payroll_number ?? null }}</td>
            <td style="text-align: left">{{ $staff['staff_name'] }}</td>
            @foreach($deductions as $deduction)
                @php
                    $deductionAmount = $staff['deductions']->where('deduction_id', $deduction->id)->first();
                    $amount = $deductionAmount ? $deductionAmount->amount : 0;
                    $totalDeductions += $amount;
                @endphp
                <td>{{ $amount > 0 ? number_format($amount, 2) : '-' }}</td>
            @endforeach
            <td>{{ number_format($totalDeductions, 2) }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="{{ 5 + $deductions->count() }}" style="text-align: center;">No deduction records found</td>
        </tr>
    @endforelse
    </tbody>
</table>
