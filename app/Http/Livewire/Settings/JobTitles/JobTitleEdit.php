<?php

namespace App\Http\Livewire\Settings\JobTitles;

use App\Models\JobTitle;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class JobTitleEdit extends Component
{
    use AuthorizesRequests;

    public $name = [];
    public $percentage;
    public $jobTitle;
    public $languages = [];


    public function mount($id)
    {
        $this->authorize('jobtitle-edit');
        $this->jobTitle = JobTitle::find($id);
        $this->percentage = $this->jobTitle->percentage;
        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = $this->jobTitle->details->where('language_id', $value->id)->first()->name ?? null;
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
            $messages['name.' . $value->id] = 'The ' . ucfirst($value->code) . ' name is required';
        }

        $validatedData = $this->validate($rules, $messages);

        $this->dispatch('saved');

        $this->jobTitle->percentage = $validatedData['percentage'];
        $this->jobTitle->save();

        foreach ($this->languages as $key => $value) {
            if ($this->jobTitle->details()->where('language_id', $value->id)->exists())
                $this->jobTitle->details()->where(
                    [
                        'language_id' => $value->id,
                        'job_title_id' => $this->jobTitle->id
                    ]
                )->update([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            else {
                $this->jobTitle->details()->create([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            }
        }

        return redirect()->route('jobtitles')
            ->with('success', 'Job Title created successfully.');
    }

    public function render()
    {
        return view('livewire.Settings.job-titles.job-title-edit');
    }
}
