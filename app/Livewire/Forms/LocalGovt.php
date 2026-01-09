<?php

namespace App\Livewire\Forms;

use App\Models\LocalGovt as LocalGovtModel;
use App\Models\State;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class LocalGovt extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $name, $state_id, $status = 1, $create = false, $edit = false, $record = true;
    public $ids, $search, $orderBy = "id", $orderAsc = true, $perpage = 15;

    protected $rules = [
        'name' => 'required',
        'state_id' => 'required',
    ];

    public function resetFields()
    {
        $this->name = '';
        $this->state_id = '';
        $this->status = 1;
    }

    public function create_post()
    {
        $this->create = true;
        $this->edit = false;
        $this->record = false;
        $this->resetFields();
    }

    public function store()
    {
        $lga = new LocalGovtModel();
        $lga->name = $this->name;
        $lga->state_id = $this->state_id;
        $lga->status = $this->status;
        $lga->save();

        $this->alert('success', 'LGA created successfully');
        $this->resetFields();
        $this->create = false;
        $this->record = true;
    }

    public function edit_record($id)
    {
        $lga = LocalGovtModel::find($id);
        $this->ids = $id;
        $this->name = $lga->name;
        $this->state_id = $lga->state_id;
        $this->status = $lga->status;

        $this->edit = true;
        $this->create = false;
        $this->record = false;
    }

    public function update()
    {
        $lga = LocalGovtModel::find($this->ids);
        $lga->name = $this->name;
        $lga->state_id = $this->state_id;
        $lga->status = $this->status;
        $lga->save();

        $this->alert('success', 'LGA updated successfully');
        $this->resetFields();
        $this->edit = false;
        $this->record = true;
    }

    public function deleteId($id)
    {
        $this->ids = $id;
        $this->alert('warning', 'Are you sure you want to delete this LGA?', [
            'showConfirmButton' => true,
            'confirmButtonText' => 'Yes',
            'onConfirmed' => 'delete',
            'showCancelButton' => true,
            'onDismissed' => 'cancelled',
            'position' => 'center',
        ]);
    }

    public function delete()
    {
        $lga = LocalGovtModel::find($this->ids);
        $lga->delete();
        $this->alert('success', 'LGA deleted successfully');
    }

    public function close()
    {
        $this->create = false;
        $this->edit = false;
        $this->record = true;
        $this->resetFields();
    }

    public function status_change($id)
    {
        $lga = LocalGovtModel::find($id);
        if ($lga->status == 1) {
            $lga->status = 0;
            $message = 'LGA discontinued successfully';
        } else {
            $lga->status = 1;
            $message = 'LGA activated successfully';
        }
        $lga->save();
        $this->alert('success', $message);
    }

    public function render()
    {
        $lgas = LocalGovtModel::with('state')
            ->when($this->search, function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhereHas('state', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc')
            ->paginate($this->perpage);

        $states = State::active()->get();

        return view('livewire.forms.local-govt', compact('lgas', 'states'))->extends('components.layouts.app');
    }
}
