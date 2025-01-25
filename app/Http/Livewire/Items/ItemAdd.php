<?php

namespace App\Http\Livewire\Items;


use Livewire\Component;
use App\Models\Item;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ItemAdd extends Component
{
    use AuthorizesRequests;

    public $selectedType;
    public $types;

    // Common fields
    public $name;
    public $costPrice;
    public $marketingAmount;
    public $deliveryAmount;
    public $otherExpenses;

    // Service-specific fields
    public $sellingPrice1;
    public $sellingPrice2;

    // Product-specific fields
    public $quantity;
    public $retailPrice;
    public $wholesalePrice;

    public function mount()
    {
        $this->authorize('item-create');
        $this->types = [
            'service' => 'خدمة',
            'product' => 'منتج'
        ];
        $this->selectedType = 'service';
    }

    public function store()
    {
        $this->validate($this->getValidationRules());

        $item = new Item();
        $item->type = $this->selectedType;
        $item->name = $this->name;
        $item->cost_price = $this->costPrice;
        $item->marketing_amount = $this->marketingAmount;
        $item->delivery_amount = $this->deliveryAmount;
        $item->other_expenses = $this->otherExpenses;
        $item->quantity = $this->quantity;
        $item->user_id = auth()->id();

        if ($this->selectedType === 'service') {
            $item->selling_price_1 = $this->sellingPrice1;
            $item->selling_price_2 = $this->sellingPrice2;
        } else {
            $item->retail_price = $this->retailPrice;
            $item->wholesale_price = $this->wholesalePrice;
        }

        $item->save();

        session()->flash('message', 'تم إنشاء العنصر بنجاح.');
        return redirect()->route('items');
    }

    private function getValidationRules()
    {
        $rules = [
            'selectedType' => 'required|in:service,product',
            'name' => 'required|string|max:255',
            'costPrice' => 'required|numeric|min:0',
            'marketingAmount' => 'required|numeric|min:0',
            'deliveryAmount' => 'required|numeric|min:0',
            'otherExpenses' => 'required|numeric|min:0',
        ];

        $rules['quantity'] = 'required|integer|min:0';

        if ($this->selectedType === 'service') {
            $rules['sellingPrice1'] = 'required|numeric|min:0';
            $rules['sellingPrice2'] = 'required|numeric|min:0';
        } else {
            $rules['retailPrice'] = 'required|numeric|min:0';
            $rules['wholesalePrice'] = 'required|numeric|min:0';
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.Items.item-add');
    }
}