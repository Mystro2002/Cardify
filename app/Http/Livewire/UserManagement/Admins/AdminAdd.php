<?php

namespace App\Http\Livewire\UserManagement\Admins;

use App\Models\Admin;
use App\Models\AdminJob;
use App\Models\City;
use App\Models\JobTitle;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Hash;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;

class AdminAdd extends Component
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

    public $address;
    public $jobTitles;
    public $job_titles = [];
    public $selected_job_titles = [];
    public $percentages = [];
    protected $listeners = [
        'cardLoaded',
    ];

    protected $rules = [
        'name' => 'required',
        'phone' => 'required',
        'address' => 'nullable',
        'email' => 'required|email|unique:admins,email',
        'password' => 'required|required_with:password_confirmation|same:password_confirmation',
        'password_confirmation' => 'required|required_with:password|same:password',
        'selectedrole' => 'required',
//        'city_id' => 'required',
//        'selected_job_titles' => 'required',
//        'percentages.*' => 'required|numeric|min:0|max:100',
    ];

    protected $messages = [
        'name.required' => 'The name field is required',
        'phone.required' => 'The phone field is required',
        'address.required' => 'The address field is required',
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
//        'city_id.required' => 'The city field is required',
//        'selected_job_titles.required' => 'The job titles field is required',
//        'percentages.*.required' => 'This value is required',
//        'percentages.*.numeric' => 'This value must be a number',
//        'percentages.*.min' => 'This value must be at least :min',
//        'percentages.*.max' => 'This value must be at most :max',
    ];

    public function mount()
    {

        $this->authorize('admin-create');
        $this->roles = Role::pluck('name', 'id')->all();
//        $this->cities = City::all();
//        $this->jobTitles = JobTitle::all();
    }

    public function cardLoaded($isBooted)
    {
        $this->notBooted = $isBooted;
    }

    public function updatedJobTitles($value)
    {
        foreach ($value as $key => $v) {
            if (!array_key_exists($v, $this->percentages)) {
                $this->percentages[$v] = JobTitle::find($v)->percentage;
            }
        }
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
                    $this->job_titles[$v] = $jobTitle->details()->first()->name;
                }
            }
        }
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $validatedData = $this->validate($this->rules, $this->messages);

        $this->dispatch('saved');

        DB::beginTransaction();

        try {
            $user = new Admin();
            $user->name = $validatedData['name'];
            $user->phone = $validatedData['phone'];
            $user->email = $validatedData['email'];
            $user->password = $validatedData['password'];
            $user->address = $validatedData['address'];
//            $user->city_id = $validatedData['city_id'];
            $user->save();

            $role = Role::find($validatedData['selectedrole']);
            $user->assignRole([$role->id]);

            foreach ($this->percentages as $key => $value) {
                $job = new AdminJob();
                $job->admin_id = $user->id;
                $job->percentage = $value;
                $job->save();
            }
            DB::commit();

            return redirect()->route('admins')->with('success', 'Admin created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('scrollToElement');

            session()->flash('error', 'An error occurred while creating the admin.');
        }
    }

    public function render()
    {
        return view('livewire.Users.admins.admin-add');
    }
}
