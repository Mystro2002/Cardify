<?php

namespace App\Http\Livewire\Settings\Countries;

use App\Models\Country;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CountryView extends Component
{
    use AuthorizesRequests;

    protected $countries;
    protected $paginationTheme = 'bootstrap';
    protected $categories = [];


    public $search = '';
    public $searchableFields = ['name'];

    protected $listeners = [
        'destroy',
    ];


    public function mount()
    {
        $this->authorize('country-list');
    }

    #[On('destroy')]
    public function destroy($id)
    {
        Country::where('id', $id)->delete();

        return redirect()->route('countries')
            ->with('success', 'Country deleted successfully');
    }

    public function render()
    {
        $this->countries = Country::searchMany($this->searchableFields, $this->search);
        return view('livewire.Settings.countries.country-view' , [
            'countries' => $this->countries,
        ]);
    }
}
