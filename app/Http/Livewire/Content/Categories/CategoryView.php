<?php

namespace App\Http\Livewire\Content\Categories;

use App\Models\Category;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class CategoryView extends Component
{
    use AuthorizesRequests;

    protected $paginationTheme = 'bootstrap';
    protected $categories = [];

    public $dataList;
    public $status = [];

    public $search = '';
    public $searchableFields = ['name'];

    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('category-list');
        $this->dataList = Category::get();
        foreach ($this->dataList as $data) {
            $this->status[$data->id] = $data->status == 1 ? true : false;
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function updatedStatus($data) {
        $menuIdsToUpdate = array_keys(array_filter($this->status));

        Category::whereIn('id', $menuIdsToUpdate)->update(['status' => 1]);
        Category::whereNotIn('id', $menuIdsToUpdate)->update(['status' => 0]);
        session()->flash('success', 'Status changed');

    }

    #[On('destroy')]
    public function destroy($id)
    {
        Category::where('id', $id)->delete();
        return redirect()->route('categories')
            ->with('success', 'Category deleted successfully');
    }

    public function render()
    {
        $this->categories = Category::searchMany($this->searchableFields, $this->search);


        return view('livewire.Content.categories.category-view', [
            'categories' => $this->categories,
        ]);
    }
}