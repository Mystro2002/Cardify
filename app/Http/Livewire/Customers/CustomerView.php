<?php

namespace App\Http\Livewire\Customers;

use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';


    public $perPage = 10;

    public $isSuperAdmin = false;

    public $search = '';
    public $searchableFields = ['name', 'phone'];

    public function mount()
    {
        $this->authorize('customer-list');
        $this->isSuperAdmin = auth()->id() === 1;

    }





    #[On('customerDeleted')]
    public function customerDeleted()
    {
        $this->resetPage();
    }

    public function deleteCustomer($id)
    {
        $this->authorize('customer-delete');
        $customer = Customer::findOrFail($id);
        $customer->delete();
        $this->emit('customerDeleted');
        session()->flash('success', 'Customer deleted successfully.');
    }

    public function render()
    {
        $user = auth()->user();
        $customers = Customer::when(!$this->isSuperAdmin , function ($q) use($user){
            $q->where('user_id',$user->id);
        })->searchMany($this->searchableFields, $this->search);

        return view('livewire.customers.customer-view', [
            'customers' => $customers,
        ]);
    }
}