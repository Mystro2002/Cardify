<?php

namespace App\Http\Livewire\Settings\Languagnes;

use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LanguageEdit extends Component
{
    use AuthorizesRequests;

    public $name;
    public $code;
    public $direction;
    public $language;

    public function mount($id)
    {
        $this->authorize('language-edit');
        $this->language = Language::find($id);
        $this->name =  $this->language->name;
        $this->code = $this->language->code;
        $this->direction = $this->language->direction;
    }

    public function store()
    {
        $this->dispatch('scrollToElement');

        $validatedData = $this->validate([
            'name' => 'required',
            'code' => 'required',
            'direction' => 'required',
        ]);

        $this->dispatch('saved');

        $this->language->name = $validatedData['name'];
        $this->language->code = $validatedData['code'];
        $this->language->direction = $validatedData['direction'];
        $this->language->save();


        return redirect()->route('languages')
            ->with('success', 'Language created successfully.');
    }


    public function render()
    {
        return view('livewire.Settings.languages.language-edit');
    }
}
