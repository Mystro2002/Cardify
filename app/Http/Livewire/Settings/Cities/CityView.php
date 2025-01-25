<?php

namespace App\Http\Livewire\Settings\Cities;

use App\Models\City;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CityView extends Component
{

    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $searchableFields = ['name'];
    protected $cities;

    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('city-list');
    }

    #[On('destroy')]
    public function destroy($id)
    {
        City::where('id', $id)->delete();

        return redirect()->route('cities')
            ->with('success', 'City deleted successfully');
    }

    public function render()
    {
        $this->cities = City::searchMany($this->searchableFields, $this->search);

        return view('livewire.Settings.cities.city-view', [
            'cities' => $this->cities,
        ]);
    }
}
