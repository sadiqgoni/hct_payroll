<div>
    <div class="row">
        <div class="col-lg-12">
            <form style="padding: 10px">
                <fieldset>
                    <legend>Salary Posting Setup</legend>
                    <div wire:loading
                        style="position: absolute;z-index: 9999;text-align: center;width: 100%;padding: 25vh;top: -50px">
                        <div style="background: rgba(14,13,13,0.13);margin: auto;max-width:100px;">
                            <i class="fa fa-spin fa-spinner" style="font-size:100px"></i>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                @error('month')
                                    <strong class="text-danger">{{$message}}</strong>
                                @enderror
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Month</span></div>
                                    <select class="form-control @error('month') is-invalid @enderror"
                                        wire:model.live="month">
                                        <option value="">Select Month</option>
                                        <option value="January">January</option>
                                        <option value="February">February</option>
                                        <option value="March">March</option>
                                        <option value="April">April</option>
                                        <option value="May">May</option>
                                        <option value="June">June</option>
                                        <option value="July">July</option>
                                        <option value="August">August</option>
                                        <option value="September">September</option>
                                        <option value="October">October</option>
                                        <option value="November">November</option>
                                        <option value="December">December</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                @error('year')
                                    <strong class="text-danger">{{$message}}</strong>
                                @enderror
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Year</span></div>
                                    <select class="form-control @error('year') is-invalid @enderror"
                                        wire:model.blur="year">
                                        @php
                                            $firstYear = \Illuminate\Support\Carbon::now()->subYears(20)->format('Y');
                                            $lastYear = \Illuminate\Support\Carbon::now()->addYears(20)->format('Y');
                                        @endphp
                                        @for($i = $firstYear; $i <= $lastYear; $i++)
                                            <option value="{{$i}}" @if(date('Y') == $i) selected @endif>{{$i}}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            @error('description')
                                <strong class="text-danger">{{$message}}</strong>
                            @enderror
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Description</span></div>
                                <input class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" type="text">
                            </div>
                            <small class="text-muted">Description is auto-generated from institution, month and year,
                                but you can still edit it.</small>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mt-4">
                    <legend>Posting Scope</legend>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                <label class="btn btn-outline-primary {{ $posting_scope == 'all' ? 'active' : '' }}"
                                    wire:click="$set('posting_scope', 'all')">
                                    <input type="radio" autocomplete="off" {{ $posting_scope == 'all' ? 'checked' : '' }}>
                                    All Active Staff
                                </label>
                                <label class="btn btn-outline-success {{ $posting_scope == 'batch' ? 'active' : '' }}"
                                    wire:click="$set('posting_scope', 'batch')">
                                    <input type="radio" autocomplete="off" {{ $posting_scope == 'batch' ? 'checked' : '' }}>
                                    Selected Staff Batch
                                </label>
                            </div>
                            <p class="text-muted small mt-2 mb-0">
                                Use batch mode when payroll should be posted for only a selected subset of staff.
                            </p>
                        </div>
                    </div>

                    @if($posting_scope === 'batch')
                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Batch Name @error('batch_name') <small class="text-danger">{{$message}}</small> @enderror</label>
                                    <input type="text" wire:model.blur="batch_name"
                                        class="form-control @error('batch_name') is-invalid @enderror"
                                        placeholder="e.g. March 2026 Batch A">
                                   
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="form-group">
                                    <label>Quick Search</label>
                                    <input type="text" wire:model.live.debounce.300ms="search" class="form-control"
                                        placeholder="Search by name, staff number or payroll number">
                                </div>
                            </div>
                        </div>

                        <legend class="mt-3">Staff Selection</legend>
                        <div class="row">
                            <div class="col-12 col-md-4">
                                @error('employee_type')
                                    <strong class="text-danger d-block form-text">{{$message}}</strong>
                                @enderror
                                <div class="input-group form-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Employment Type</span></div>
                                    <select class="form-control @error('employee_type') is-invalid @enderror"
                                        wire:model.blur="employee_type">
                                        <option value="">Employment Type</option>
                                        @foreach($types as $emp)
                                            <option value="{{$emp->id}}">{{$emp->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                @error('staff_category')
                                    <strong class="text-danger d-block form-text">{{$message}}</strong>
                                @enderror
                                <div class="input-group form-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Staff Category</span></div>
                                    <select class="form-control @error('staff_category') is-invalid @enderror"
                                        wire:model.blur="staff_category">
                                        <option value="">Staff Category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{$cat->id}}">{{$cat->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="input-group form-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Unit</span></div>
                                    <select class="form-control" wire:model.blur="unit">
                                        <option value="">Select Unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{$unit->id}}">{{$unit->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <div class="input-group form-group">
                                    <div class="input-group-prepend"><span class="input-group-text">Department</span></div>
                                    <select class="form-control" wire:model.blur="department">
                                        <option value="">Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{$dept->id}}">{{$dept->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                           
                        </div>

                        @include('livewire.forms.ssd')

                        <div class="row mt-3">
                            <div class="col-12">
                                @error('specific_employee_ids')
                                    <strong class="text-danger d-block form-text">{{ $message }}</strong>
                                @enderror
                                @php $allCandidateIds = $specificCandidates->flatten()->pluck('id')->toArray(); @endphp
                                <div class="mb-3 d-flex flex-wrap align-items-center">
                                    <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2"
                                        wire:click.prevent="selectAllEmployees({{ json_encode($allCandidateIds) }})">
                                        <i class="fa fa-check-square-o"></i> Select All  ({{ count($allCandidateIds) }})
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mr-3 mb-2"
                                        wire:click.prevent="deselectAllEmployees({{ json_encode($allCandidateIds) }})">
                                        <i class="fa fa-square-o"></i> Deselect All 
                                    </button>
                                    <span class="badge badge-success mb-2" style="font-size: 0.95rem;">
                                        {{ count($specific_employee_ids) }} selected
                                    </span>
                                </div>

                                <div class="border rounded p-3"
                                    style="max-height: 520px; overflow-y: auto; background: #f8f9fa;">
                                    <div class="row">
                                        @forelse($specificCandidates as $groupName => $employees)
                                            @php $groupIds = $employees->pluck('id')->toArray(); @endphp
                                            <div class="col-12 col-md-6 col-xl-4 mb-4">
                                                <div class="card shadow-sm h-100 border-primary">
                                                    <div class="card-header bg-white py-2 border-bottom">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0 font-weight-bold text-dark"
                                                                style="font-size: 0.9rem;">{{ $groupName }}</h6>
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge badge-primary mr-2">{{ count($employees) }}</span>
                                                                <button type="button"
                                                                    class="btn btn-xs btn-outline-primary px-1 py-0"
                                                                    style="font-size:0.75rem;"
                                                                    wire:click.prevent="selectAllEmployees({{ json_encode($groupIds) }})">All</button>
                                                                <button type="button"
                                                                    class="btn btn-xs btn-outline-secondary px-1 py-0 ml-1"
                                                                    style="font-size:0.75rem;"
                                                                    wire:click.prevent="deselectAllEmployees({{ json_encode($groupIds) }})">None</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-0">
                                                        <div class="list-group list-group-flush"
                                                            style="max-height: 250px; overflow-y: auto;">
                                                            @foreach ($employees as $emp)
                                                                <label
                                                                    class="list-group-item list-group-item-action d-flex align-items-center px-3 py-2 mb-0">
                                                                    <input type="checkbox" class="mr-3"
                                                                        value="{{ $emp->id }}"
                                                                        wire:model="specific_employee_ids">
                                                                    <div class="d-flex flex-column"
                                                                        style="line-height: 1.2;">
                                                                        <span class="font-weight-bold text-dark"
                                                                            style="font-size: 0.85rem;">{{ $emp->full_name }}</span>
                                                                        <small
                                                                            class="text-muted">{{ $emp->staff_number ?: $emp->payroll_number }}</small>
                                                                    </div>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12 text-center py-5 text-muted">
                                                <p class="mb-0">No employees match the current filters.</p>
                                                <small>Adjust the filters above to load candidates for this batch.</small>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </fieldset>

                <button wire:click.prevent="store()" class="btn mt-3 save_btn" type="button">Post Salary</button>
            </form>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-lg-12">
            <fieldset>
                <legend><i class="fa fa-history"></i> Recently Posted Salaries</legend>
                <div class="row">
                    @if($recentSalaries->count() > 0)
                        @foreach($recentSalaries as $salary)
                            <div class="col-12 col-md-6 col-lg-4 mb-3">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"
                                            style="background: #007bff; color: white; border: 1px solid #007bff;">
                                            <i class="fa fa-calendar"></i>
                                        </span>
                                    </div>
                                    <div class="form-control" style="background: #f8f9fa;">
                                        <strong>{{ $salary->salary_month }} {{ $salary->salary_year }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ $salary->salary_posting_batch_name ?: 'All Active Staff' }}
                                        </small>
                                        <br>
                                        <small class="text-muted">{{ $salary->staff_count }} staff{{ $salary->staff_count != 1 ? 's' : '' }}</small>
                                    </div>
                                    <div class="input-group-append">
                                        <span class="input-group-text">
                                            <span class="badge badge-primary">{{ $salary->staff_count }}</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <div class="text-center text-muted py-4"
                                style="border: 1px dashed #dee2e6; border-radius: 5px;">
                                <i class="fa fa-inbox fa-2x mb-2"></i>
                                <p class="mb-0">No salary records posted yet</p>
                            </div>
                        </div>
                    @endif
                </div>
            </fieldset>
        </div>
    </div>

    @section('title')
        Salary Ledger Posting
    @endsection
    @section('page_title')
        Payroll Update / Salary Posting
    @endsection
</div>
