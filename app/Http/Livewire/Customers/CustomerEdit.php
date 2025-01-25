<?php

namespace App\Http\Livewire\Customers;

use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerEdit extends Component
{
    use AuthorizesRequests;

    public $customer;
    public $name;
    public $governorate;
    public $landmark;
    public $address;
    public $phone;
    public $secondary_phone;

    protected $rules = [
        'name' => 'required|string|max:255',
        'governorate' => 'required|string|max:255',
        'landmark' => 'required|string|max:255',
        'address' => 'required|string|max:255',
        'phone' => 'required|string|max:20',
        'secondary_phone' => 'nullable|string|max:20',
    ];

    public function mount($id)
    {
        $this->authorize('customer-edit');

        $this->customer = Customer::findOrFail($id);
        $this->name = $this->customer->name;
        $this->governorate = $this->customer->governorate;
        $this->landmark = $this->customer->landmark;
        $this->address = $this->customer->address;
        $this->phone = $this->customer->phone;
        $this->secondary_phone = $this->customer->secondary_phone;
    }

    public function update()
    {
        $this->validate();

        try {
            DB::beginTransaction();

            $this->customer->update([
                'name' => $this->name,
                'governorate' => $this->governorate,
                'landmark' => $this->landmark,
                'address' => $this->address,
                'phone' => $this->phone,
                'secondary_phone' => $this->secondary_phone,
            ]);

            DB::commit();

            session()->flash('message', 'Customer updated successfully.');
            return redirect()->route('customers');

        } catch (\Exception $ex) {
            DB::rollBack();
            session()->flash('error', 'An error occurred while updating the customer.');
        }
    }

    public function render()
    {
        return view('livewire.customers.customer-edit');
    }
}