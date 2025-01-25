<?php

namespace App\Http\Livewire\Customers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Models\Customer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CustomerAdd extends Component
{
    use AuthorizesRequests;

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

    public function mount()
    {
        if (!auth()->user()->hasRole('client')) {
            $this->authorize('customer-create');
        }
    }


    public function store()
    {
        $this->validate();
        DB::beginTransaction();
        try {


            $customerData = [
                'name' => $this->name,
                'governorate' => $this->governorate,
                'landmark' => $this->landmark,
                'address' => $this->address,
                'phone' => $this->phone,
                'secondary_phone' => $this->secondary_phone,
                'user_id' => auth()->id(),
            ];

           // dd($customerData);

            \Log::info('Customer data before creation:', $customerData);

            $customer = Customer::create($customerData);
//            dd($customerData);

            \Log::info('Created customer:', $customer->toArray());

            DB::commit();

            $this->dispatch('saved');
            session()->flash('success', 'تم إنشاء العميل بنجاح.');

            return redirect()->route('customers');
        } catch (\Exception $ex) {
            DB::rollBack();

            \Log::error('Error creating customer: ' . $ex->getMessage(), [
                'exception' => $ex,
                'trace' => $ex->getTraceAsString(),
                'user_id' => auth()->id(),
                'user_roles' => auth()->user()->roles->pluck('name'),
            ]);

            session()->flash('error', 'حدث خطأ أثناء إنشاء العميل: ' . $ex->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.customers.customer-add');
    }
}