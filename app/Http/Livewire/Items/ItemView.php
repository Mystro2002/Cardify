<?php

namespace App\Http\Livewire\Items;

use App\Models\Customer;
use App\Models\Item;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ItemView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $items;
    public $selectedUser;

    public $search = '';
    public $searchableFields = ['name'];


    public $isSuperAdmin = false;


    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('item-list');

        $this->isSuperAdmin = auth()->id() === 1;


    }

    public function updatingSearch()
    {
        $this->resetPage();
    }


    #[On('destroy')]
    public function destroy($id)
    {
        Item::where('id', $id)->delete();

        return redirect()->route('items')
            ->with('success', 'Item deleted successfully');
    }
    public function render()
    {
        $user = auth()->user();
        $items = Item::when(!$this->isSuperAdmin , function ($q) use($user){
            $q->where('user_id',$user->id);
        })->searchMany($this->searchableFields, $this->search);

        return view('livewire.Items.item-view', [
            'items' => $items,
        ]);
    }
}
