<?php

namespace App\Http\Livewire\UserManagement\Admins;

use App\Models\Admin;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class AdminView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $user;

    public $adminList;
    public $neverExpired = [];

    public $search = '';
    public $searchableFields = ['name', 'email'];

    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('admin-list');

        $this->adminList = Admin::get();
        foreach ($this->adminList as $menu) {
            $this->neverExpired[$menu->id] = $menu->status == 1 ? true : false;
        }
    }

    public function updatedNeverExpired($data) {
        $menuIdsToUpdate = array_keys(array_filter($this->neverExpired));

        Admin::whereIn('id', $menuIdsToUpdate)->update(['status' => 1]);
        Admin::whereNotIn('id', $menuIdsToUpdate)->update(['status' => 0]);

        session()->flash('success', 'Status changed');

    }

    public function updatingSearch()
    {
        $this->resetPage();
    }
 
    #[On('destroy')]
    public function destroy($id)
    {
        Admin::where('id', $id)->delete();

        return redirect()->route('admins')
            ->with('success', 'Admin deleted successfully');
    }

    public function render()
    {
        $this->user = Admin::searchMany($this->searchableFields, $this->search);


        return view('livewire.Users.admins.admin-view', [
            'admins' => $this->user,
        ]);
    }
}
