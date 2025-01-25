<?php

namespace App\Http\Livewire\Settings\Countries;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CountryAdd extends Component
{

    use AuthorizesRequests;

    public $name = [];
    public $languages = [];


    public function mount()
    {
        $this->authorize('country-create');

        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = '';
        }
    }

    public function store()
    {
        $this->dispatch('scrollToElement');


        $rules = [];
        $messages = [];

        foreach ($this->languages as $key => $value) {
            $rules['name.' . $value->id] = 'required';
            $messages['name.' . $value->id] = 'The ' . ucfirst($value->code) . ' name is required';
        }


        $this->dispatch('saved');
        $validatedData = $this->validate($rules, $messages);

        $country = new Country();
        $country->save();

        foreach ($this->languages as $key => $value) {
            $country->details()->create([
                'country_id' => $country->id,
                'language_id' => $value->id,
                'name' => $validatedData['name'][$value->id],
            ]);
        }


        return redirect()->route('countries')
            ->with('success', 'Country created successfully.');
    }


    public function render()
    {
        return view('livewire.Settings.countries.country-add');
    }
}
