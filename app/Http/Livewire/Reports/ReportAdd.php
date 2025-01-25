<?php

namespace App\Http\Livewire\Reports;

use App\Models\Report;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportAdd extends Component
{
    use AuthorizesRequests;

    public $notBooted = true;

    public $roles;

    public $selectedrole;
    public $name;
    public $phone;
    public $email;
    public $password;
    public $password_confirmation;

    protected $listeners = [
        'cardLoaded',
    ];

    public function mount()
    {

        $this->authorize('report-create');

        $this->roles = Role::pluck('name', 'id')->all();
    }

    public function cardLoaded($isBooted)
    {
        $this->notBooted = $isBooted;
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $validatedData = $this->validate([
            'name' => 'required',
            'phone' => 'required|numeric',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|required_with:password_confirmation|same:password_confirmation',
            'password_confirmation' => 'required|required_with:password|same:password',
            'selectedrole' => 'required',
        ]);

        $this->dispatch('saved');


        $user = new Report();
        $user->name = $validatedData['name'];
        $user->phone = $validatedData['phone'];
        $user->email = $validatedData['email'];
        $user->password = $validatedData['password'];

        $user->save();
        
        $role = Role::find($validatedData['selectedrole']);
        $user->assignRole([$role->id]);

        return redirect()->route('users')
            ->with('success', 'User created successfully.');
    }

    public function render()
    {
        return view('livewire.Reports.report-add');
    }
}
