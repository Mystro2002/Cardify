<?php

namespace App\Http\Livewire\Settings\Languagnes;

use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class LanguageAdd extends Component
{

    use AuthorizesRequests;

    public $name;
    public $code;
    public $direction;


    public function mount()
    {
        $this->authorize('language-create');
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

        $user = new Language();
        $user->name = $validatedData['name'];
        $user->code = $validatedData['code'];
        $user->direction = $validatedData['direction'];
        $user->save();


        return redirect()->route('languages')
            ->with('success', 'Language created successfully.');
    }


    public function render()
    {
        return view('livewire.Settings.languages.language-add');
    }
}
