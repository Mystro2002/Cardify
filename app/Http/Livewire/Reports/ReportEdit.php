<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Spatie\Permission\Models\Role;
use App\Models\User;
use DB;
use Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReportEdit extends Component
{
    use AuthorizesRequests;

    public $user;
    public $roles;
    public $notBooted = true;

    public $selectedrole;
    public $name;
    public $phone;
    public $email;
    public $password;
    public $password_confirmation;

    protected $listeners = [
        'cardLoaded',
    ];

    public function mount($id)
    {
        $this->authorize('report-edit');

        $this->roles = Role::pluck('name', 'id')->all();
        $this->user = User::find($id);
        $this->name = $this->user->name;
        $this->phone = $this->user->phone;
        $this->email = $this->user->email;
        $this->selectedrole = count($this->user->roles) > 0 ? Role::where('name', $this->user->getRoleNames()[0])->first()->id : null;
    }

    public function cardLoaded($isBooted)
    {
        $this->notBooted = $isBooted;
    }

    public function update()
    {
        // $this->dispatch('scrollToElement');

        $rules = [
            'name' => 'required',
            'phone' => 'required|numeric',
            'email' => 'required|email',
            'selectedrole' => 'required',
        ];

        if (!empty($this->password) || !empty($this->password_confirmation)) {
            $rules['password'] = 'required|required_with:password_confirmation|same:password_confirmation';
            $rules['password_confirmation'] = 'required|required_with:password|same:password';
        }

        $validatedData = $this->validate($rules);

        // $this->dispatch('saved');

        if (isset($validatedData['password'])) {
        $this->user->password = $validatedData['password'];
        }

        $this->user->name = $validatedData['name'];
        $this->user->phone = $validatedData['phone'];
        $this->user->email = $validatedData['email'];

        $this->user->syncRoles([]);

        $this->user->save();
      
        if ($validatedData['selectedrole']) {

            $role = Role::find($validatedData['selectedrole']);
            $this->user->assignRole($role->name);
        }
        return redirect()->route('users')
            ->with('success', 'User Updated successfully.');
    }

    public function render()
    {
        return view('livewire.Reports.report-edit');
    }
}
