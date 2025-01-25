<?php

namespace App\Http\Livewire\Settings\JobTitles;

use App\Models\JobTitle;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class JobTitleAdd extends Component
{
    use AuthorizesRequests;

    public $name = [];
    public $percentage;
    public $languages = [];


    public function mount()
    {
        $this->authorize('jobtitle-create');
        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = '';
        }
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $rules = [
            'percentage' => 'required',
        ];
        $messages = [
            'percentage.required' => 'Percentage is required',
        ];
        foreach ($this->languages as $key => $value) {
            $rules['name.' . $value->id] = 'required';
            $messages['name.' . $value->id] = 'The '.ucfirst($value->code).' name is required';
        }

        $validatedData = $this->validate($rules, $messages);

        $this->dispatch('saved');

        $jobTitle = new JobTitle();
        $jobTitle->percentage = $validatedData['percentage'];
        $jobTitle->save();

        foreach ($this->languages as $key => $value) {
            $jobTitle->details()->create([
                'language_id' => $value->id,
                'name' => $validatedData['name'][$value->id]
            ]);
        }


        return redirect()->route('jobtitles')
            ->with('success', 'Job Title created successfully.');
    }

    public function render()
    {
        return view('livewire.Settings.job-titles.job-title-add');
    }
}
