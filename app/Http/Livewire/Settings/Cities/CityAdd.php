<?php

namespace App\Http\Livewire\Settings\Cities;

use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CityAdd extends Component
{

    use AuthorizesRequests;

    public $name = [];
    public $country_id;
    public $countries;
    public $languages = [];


    public function mount()
    {
        $this->authorize('city-create');
        $this->countries = Country::all();
        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] =  null;
        }
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $rules = [
            'country_id' => 'required',
        ];
        $messages = [
            'country_id' => 'Country is required',
        ];

        foreach ($this->languages as $key => $value) {
            $rules['name.' . $value->id] = 'required';
            $messages['name.' . $value->id] = 'The ' . ucfirst($value->code) . ' name is required';
        }

        $this->dispatch('saved');

        $validatedData = $this->validate($rules, $messages);

        $city = new City();
        $city->country_id = $validatedData['country_id'];
        $city->save();
        foreach ($this->languages as $key => $value) {
            $city->details()->create([
                'city_id' => $city->id,
                'language_id' => $value->id,
                'name' => $validatedData['name'][$value->id],
            ]);
        }

        return redirect()->route('cities')
            ->with('success', 'City created successfully.');
    }


    public function render()
    {
        return view('livewire.Settings.cities.city-add');
    }
}
