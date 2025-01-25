<?php

namespace App\Http\Livewire\UserManagement\Users;

use Livewire\Component;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class UserView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $user;
    public $selectedUser;

    public $search = '';
    public $searchableFields = ['name', 'email'];
    
    protected $listeners = [
        'destroy',
    ];

    public function mount() 
    { 
        $this->authorize('user-list');

        $this->selectedUser = new User();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getSelectedUser($id)
    {
        $this->selectedUser = User::find($id);
    }
    
    #[On('destroy')]
    public function destroy($id)
    {
        User::where('id', $id)->delete();

        return redirect()->route('users')
            ->with('success', 'User deleted successfully');
    }

    public function render()
{
        $this->user = User::searchMany($this->searchableFields, $this->search);


        return view('livewire.Users.users.user-view',[
            'user' => $this->user,
        ]);
    }
}
