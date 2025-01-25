<?php

namespace App\Http\Livewire\UserManagement\Roles;

use DB;
use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class RoleView extends Component
{
    use AuthorizesRequests , WithPagination;

    protected $role;
    protected $paginationTheme = 'bootstrap';
    public $searchableFields = ['name'];
    public $search = '';

    protected $listeners = [
        'destroy',
    ];

    public function mount() 
    { 
        $this->authorize('role-list');
        
    }

    #[On('destroy')]
    public function destroy($id)
    {
        Role::where('id', $id)->delete();
        
        return redirect()->route('roles')
            ->with('success', 'Role deleted successfully');
    }

    public function render()
    {
        $this->role = Role::searchMany($this->searchableFields, $this->search);

        return view('livewire.Users.roles.role-view' , ['role'=>$this->role]);
    }
}
