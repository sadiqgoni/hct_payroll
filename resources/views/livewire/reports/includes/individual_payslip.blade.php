{{--<h6 class="text-center text-dark">KEEP RECORD OF ANNUAL INCREMENT HISTORY</h6>--}}
<style>
    sup {
        color: orangered;
    }
</style>


@can('can_mail')
    <button class="btn export float-right" wire:click.prevent="sendMail()">Send Mail <i class="fa fa-envelope"></i></button>
@endcan

@forelse($payslips as $paySlip)
    <?php
        $a = explode(" ", $paySlip->deduction_countdown);
        $sorts = collect($a)->sort();
        $loan = array();
        $search = "(";
        foreach ($sorts as $sort) {
            $loan[\Illuminate\Support\Str::before($sort, $search)] = "(" . \Illuminate\Support\Str::after($sort, '(');
        }
        ?>
    <table style="font-weight:bolder;margin:30px 9px 9px 9px ;width: 100%">
        <thead>
            <tr>
                <td style="max-width: 60px !important;"><img src="{{public_path('storage/' . app_settings()->logo)}}" alt=""
                        style="width: 70px;position: relative;"></td>
                <td style="text-align: left;padding: 0 !important;">

                    <p style="padding: 0 !important;text-align: center;margin: 0">{{app_settings()->name}}</p>
                    <p style="padding: 0 !important;text-align: center;margin: 0">{{address()}}</p>
                    <p style="padding: 0 !important;text-align: center;">Employee Pay Slip for the Month of
                        {{$paySlip->salary_month}}, {{$paySlip->salary_year}}</p>
                </td>
                <td style="width: 60px !important;">&nbsp;</td>
            </tr>
            {{-- <tr>--}}
                {{-- <td rowspan="3" style="max-width: 60px !important;"> <img
                        src="{{public_path('storage/'.app_settings()->logo)}}" alt=""
                        style="width: 70px;position: relative;"></td>--}}
                {{-- <td style="font-weight: bolder;padding: 0!important;">
                    <p>{{app_settings()->name}}</p>
                </td>--}}
                {{-- </tr>--}}
            {{-- <tr>--}}
                {{-- <td style="padding-left: 5% !important;padding-top: 0 !important;padding-bottom: 0 !important;">
                    <h4 style="padding: 0;margin: 0;">{{address()}}</h4>
                </td>--}}
                {{-- </tr>--}}
            {{-- <tr>--}}
                {{-- <td style="padding: 10px 0 0 5%" colspan="2">
                    <h5 style="padding: 0;margin: 0; ">Employee Pay Slip for the Month of {{$paySlip->salary_month}},
                        {{$paySlip->salary_year}}</h5>
                </td>--}}
                {{-- </tr>--}}
        </thead>

    </table>
        <table style="width: 75%;margin-left: 3%;font-size: 13px !important;">

            @php
                $step = optional(\App\Models\EmployeeProfile::where('staff_number', $paySlip->pf_number)->first())->step;
                $allowances = \App\Models\Allowance::where('status', 1)->get();
                $deductions = \App\Models\Deduction::where('status', 1)->get();
                $maxRows = max($allowances->count(), $deductions->count() - 1);
            @endphp

            <tbody>
                <tr>
                    <td style="margin-top: 40px;">Basic Sal: <br> Sal Arrears: </td>
                    <td style="text-align: right"> {{round($paySlip->basic_salary, 2)}}
                        <br>{{number_format($paySlip->salary_areas, 2)}}</td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </td>

                    <td colspan="2"><b>Deductions</b></td>
                </tr>
                <tr>
                    <td colspan="2"><b>Allowances</b></td>
                    <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    </td>

                    @if($deductions->count() > 0)
                        <td>{{$deductions[0]->deduction_name}}:</td>
                        <td style="text-align: right">{{number_format($paySlip->{'D' . $deductions[0]->id}, 2)}}
                            <sup>@if(array_key_exists('D' . $deductions[0]->id, $loan)){{$loan['D' . $deductions[0]->id]}}@endif</sup>
                        </td>
                    @else
                        <td colspan="2"></td>
                    @endif
                </tr>

                @for($i = 0; $i < $maxRows; $i++)
                    <tr>
                        @if(isset($allowances[$i]))
                            <td>{{$allowances[$i]->allowance_name}}: </td>
                            <td style="text-align: right">{{number_format($paySlip->{'A' . $allowances[$i]->id}, 2)}}</td>
                        @else
                            <td colspan="2"></td>
                        @endif

                        <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        </td>

                        @if(isset($deductions[$i + 1]))
                            <td>{{$deductions[$i + 1]->deduction_name}}: </td>
                            <td style="text-align: right">{{number_format($paySlip->{'D' . $deductions[$i + 1]->id}, 2)}}
                                <sup>@if(array_key_exists('D' . $deductions[$i + 1]->id, $loan)){{$loan['D' . $deductions[$i + 1]->id]}}@endif</sup>
                            </td>
                        @else
                            <td colspan="2"></td>
                        @endif
                    </tr>
                @endfor

                <tr>
                    <td style="font-weight: bolder" colspan="4"><b>Gross Pay:{{number_format($paySlip->gross_pay, 2)}}</b>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bolder" colspan="4"><b>Total
                            Ded:{{number_format($paySlip->total_deduction, 2)}}</b></td>
                </tr>
                <tr>
                    <td style="font-weight: bolder" colspan="4"><b>Net Pay: {{number_format($paySlip->net_pay, 2)}}</b></td>

                </tr>
            </tbody>
        </table>
@empty
        No record found
    @endforelse