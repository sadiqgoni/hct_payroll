<?php

namespace App\Livewire\Forms;

use App\Models\State as StateModel;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

class State extends Component
{
    use WithPagination;
    use LivewireAlert;

    public $name, $status = 1, $create = false, $edit = false, $record = true;
    public $ids, $search, $orderBy = "id", $orderAsc = true, $perpage = 15;

    protected $rules = [
        'name' => 'required',
    ];

    public function resetFields()
    {
        $this->name = '';
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
        $state = new StateModel();
        $state->name = $this->name;
        $state->status = $this->status;
        $state->country = 1; // Default to Nigeria
        $state->save();

        $this->alert('success', 'State created successfully');
        $this->resetFields();
        $this->create = false;
        $this->record = true;
    }

    public function edit_record($id)
    {
        $state = StateModel::find($id);
        $this->ids = $id;
        $this->name = $state->name;
        $this->status = $state->status;

        $this->edit = true;
        $this->create = false;
        $this->record = false;
    }

    public function update()
    {
        $state = StateModel::find($this->ids);
        $state->name = $this->name;
        $state->status = $this->status;
        $state->save();

        $this->alert('success', 'State updated successfully');
        $this->resetFields();
        $this->edit = false;
        $this->record = true;
    }

    public function deleteId($id)
    {
        $this->ids = $id;
        $this->alert('warning', 'Are you sure you want to delete this state?', [
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
        $state = StateModel::find($this->ids);
        $state->delete();
        $this->alert('success', 'State deleted successfully');
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
        $state = StateModel::find($id);
        if ($state->status == 1) {
            $state->status = 0;
            $message = 'State discontinued successfully';
        } else {
            $state->status = 1;
            $message = 'State activated successfully';
        }
        $state->save();
        $this->alert('success', $message);
    }

    public function render()
    {
        $states = StateModel::when($this->search, function ($query) {
            return $query->where('name', 'like', '%' . $this->search . '%');
        })
        ->orderBy($this->orderBy, $this->orderAsc ? 'asc' : 'desc')
        ->paginate($this->perpage);

        return view('livewire.forms.state', compact('states'))->extends('components.layouts.app');
    }
}
