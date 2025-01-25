<?php

namespace App\Http\Livewire\Settings\Countries;

use App\Models\Country;
use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CountryEdit extends Component
{
    use AuthorizesRequests;

    public $name = [];
    public $languages = [];
    public $country;



    public function mount($id)
    {
        $this->authorize('country-edit');
        $this->country = Country::findOrFail($id);
        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = $this->country->details->where('language_id', $value->id)->first()->name ?? null;
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

        $validatedData = $this->validate($rules, $messages);

        $this->dispatch('saved');

        foreach ($this->languages as $key => $value) {
            if ($this->country->details()->where('language_id', $value->id)->exists())
                $this->country->details()->where(
                    [
                        'language_id' => $value->id,
                        'country_id' => $this->country->id
                    ]
                )->update([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            else {
                $this->country->details()->create([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id]
                ]);
            }
        }

        return redirect()->route('countries')
            ->with('success', 'Country updated successfully.');
    }

    public function render()
    {
        return view('livewire.Settings.countries.country-edit');
    }
}
