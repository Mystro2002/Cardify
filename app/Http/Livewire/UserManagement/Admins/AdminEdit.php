<?php

namespace App\Http\Livewire\UserManagement\Admins;

use App\Models\Admin;
use App\Models\AdminJob;
use App\Models\City;
use App\Models\JobTitle;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class AdminEdit extends Component
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
    public $address;
    public $password_confirmation;

    public $jobTitles;
    public $job_titles = [];
    public $selected_job_titles = [];
    public $percentages = [];
    public $oldpercentages = [];


    protected $messages = [
        'name.required' => 'The name field is required',
        'phone.required' => 'The phone field is required',
        'address.nullable' => 'The address field is required',
        'email.required' => 'The email field is required',
        'email.email' => 'The email must be a valid email address',
        'email.unique' => 'The email has already been taken',
        'password.required' => 'The password field is required',
        'password.required_with' => 'The password field is required when confirming password',
        'password.same' => 'The password and confirmation password must match',
        'password_confirmation.required' => 'The confirmation password field is required',
        'password_confirmation.required_with' => 'The confirmation password field is required when password is present',
        'password_confirmation.same' => 'The confirmation password and password must match',
        'selectedrole.required' => 'The role field is required',
        'percentages.*.required' => 'This value is required',
        'percentages.*.numeric' => 'This value must be a number',
        'percentages.*.min' => 'This value must be at least :min',
        'percentages.*.max' => 'This value must be at most :max',
    ];

    protected $listeners = [
        'cardLoaded',
    ];

    public function mount($id)
    {
        $this->authorize('user-edit');
        $this->jobTitles = JobTitle::all();
        $this->roles = Role::pluck('name', 'id')->all();
        $this->user = Admin::find($id);
        $this->name = $this->user->name;
        $this->phone = $this->user->phone;
        $this->email = $this->user->email;
        $this->address = $this->user->address;
        $this->selectedrole = count($this->user->roles) > 0 ? Role::where('name', $this->user->getRoleNames()[0])->first()->id : null;
        $this->selected_job_titles =   count($this->user->titles) > 0 ? $this->user->titles->pluck('job_title_id')->toArray() : [];

        foreach ($this->selected_job_titles as $v) {
            $jobTitle = JobTitle::find($v);
            if ($jobTitle && !array_key_exists($v, $this->oldpercentages)) {
                $this->oldpercentages[$v] = $this->user->titles->where('job_title_id', $v)->first()->percentage;
                $this->job_titles[$v] = $jobTitle->details()->first()->name;
            }
        }
        $this->percentages = $this->oldpercentages;
    }

    public function updatedSelectedJobTitles($value)
    {
        if (count($this->selected_job_titles) == 0) {
            $this->percentages = [];
            $this->job_titles = [];
            $this->selected_job_titles = [];
        }

        // Remove percentages for job titles that are no longer selected
        foreach ($this->percentages as $key => $percentage) {
            if (!in_array($key, $this->selected_job_titles)) {
                unset($this->percentages[$key]);
                unset($this->job_titles[$key]);
            }
        }

        // Add percentages for newly selected job titles
        foreach ($this->selected_job_titles as $v) {
            if (!array_key_exists($v, $this->percentages)) {
                $jobTitle = JobTitle::find($v);
                if ($jobTitle) {
                    $this->percentages[$v] = $jobTitle->percentage;
                    $this->job_titles[$v] = $jobTitle->name;
                }
            }
        }
    }



    public function cardLoaded($isBooted)
    {
        $this->notBooted = $isBooted;
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $rules = [
            'name' => 'required',
            'phone' => 'required',
            'address' => 'required',
            'email' => 'required|email|unique:admins,email,' . $this->user->id,
            'selectedrole' => 'required',
        ];

        if (!empty($this->password) || !empty($this->password_confirmation)) {
            $rules['password'] = 'required|required_with:password_confirmation|same:password_confirmation';
            $rules['password_confirmation'] = 'required|required_with:password|same:password';
        }

        $validatedData = $this->validate($rules, $this->messages);

        $this->dispatch('saved');
        DB::beginTransaction();

        try {
            if (isset($validatedData['password'])) {
                $this->user->password = $validatedData['password'];
            }

            $this->user->name = $validatedData['name'];
            $this->user->phone = $validatedData['phone'];
            $this->user->email = $validatedData['email'];
            $this->user->address = $validatedData['address'];

            $this->user->syncRoles([]);

            $this->user->save();

            if ($validatedData['selectedrole']) {

                $role = Role::find($validatedData['selectedrole']);
                $this->user->assignRole($role->name);
            }

            AdminJob::where('admin_id', $this->user->id)->delete();
            foreach ($this->percentages as $key => $value) {
                $job = new AdminJob();
                $job->admin_id = $this->user->id;
                $job->percentage = $value;
                $job->job_title_id = $key;
                $job->save();
            }

            DB::commit();

            return redirect()->route('admins')->with('success', 'Admin Updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('scrollToElement');

            session()->flash('error', 'An error occurred while creating the admin.');
        }
    }

    public function render()
    {
        return view('livewire.Users.admins.admin-edit');
    }
}
