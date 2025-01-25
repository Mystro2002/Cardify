<?php

namespace App\Http\Livewire\Settings\Cities;

use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CityEdit extends Component
{
    use AuthorizesRequests;

    public $name = [];
    public $country_id;
    public $countries;
    public $languages = [];
    public $city;


    public function mount($id)
    {
        $this->authorize('city-create');
        $this->countries = Country::all();
        $this->city = City::findOrFail($id);
        $this->country_id = $this->city->country_id;
        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = $this->city->details->where('language_id', $value->id)->first()->name ?? null;
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

        $this->city->country_id = $validatedData['country_id'];
        $this->city->save();

        foreach ($this->languages as $key => $value) {
            if ($this->city->details()->where('language_id', $value->id)->exists())
                $this->city->details()->where(
                    [
                        'language_id' => $value->id,
                        'city_id' => $this->city->id
                    ]
                )->update([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            else {
                $this->city->details()->create([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            }
        }

        return redirect()->route('cities')
            ->with('success', 'City created successfully.');
    }

    public function render()
    {
        return view('livewire.Settings.cities.city-edit');
    }
}
