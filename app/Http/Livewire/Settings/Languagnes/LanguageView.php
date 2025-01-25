<?php

namespace App\Http\Livewire\Settings\Languagnes;

use App\Models\Language;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class LanguageView extends Component
{
    use AuthorizesRequests,WithPagination;

    protected  $languages;

    protected $listeners = [
        'destroy',
    ];
    
    public $searchableFields = ['name'];
    public $search = '';

    public function mount()
    {
        $this->authorize('language-list');

    }

    #[On('destroy')]
    public function destroy($id)
    {
        Language::where('id', $id)->delete();

        return redirect()->route('languages')
            ->with('success', 'Language deleted successfully');
    }

    public function render()
    {
        $this->languages =  Language::searchMany($this->searchableFields, $this->search);
        return view('livewire.Settings.languages.language-view' , [
            'languages'=>   $this->languages
        ]);
    }
}
