<?php

namespace App\Http\Livewire\Items;

use Livewire\Component;
use App\Models\Item;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ItemEdit extends Component
{
    use AuthorizesRequests;

    public $item;
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

    public function mount($id)
    {
        $this->authorize('item-edit');
        $this->item = Item::findorfail($id);
        $this->types = [
            'service' => 'خدمة',
            'product' => 'منتج'
        ];
        $this->selectedType =  $this->item->type;

        // Load item data
        $this->name =  $this->item->name;
        $this->costPrice =  $this->item->cost_price;
        $this->marketingAmount =  $this->item->marketing_amount;
        $this->deliveryAmount =  $this->item->delivery_amount;
        $this->otherExpenses =  $this->item->other_expenses;
        $this->quantity =  $this->item->quantity;

        if ( $this->item->type === 'service') {
            $this->sellingPrice1 =  $this->item->selling_price_1;
            $this->sellingPrice2 =  $this->item->selling_price_2;
        } else {
            $this->retailPrice =  $this->item->retail_price;
            $this->wholesalePrice =  $this->item->wholesale_price;
        }
    }

    public function update()
    {
        $this->validate($this->getValidationRules());

        $this->item->type = $this->selectedType;
        $this->item->name = $this->name;
        $this->item->cost_price = $this->costPrice;
        $this->item->marketing_amount = $this->marketingAmount;
        $this->item->delivery_amount = $this->deliveryAmount;
        $this->item->other_expenses = $this->otherExpenses;
        $this->item->quantity = $this->quantity;

        if ($this->selectedType === 'service') {
            $this->item->selling_price_1 = $this->sellingPrice1;
            $this->item->selling_price_2 = $this->sellingPrice2;
            $this->item->retail_price = null;
            $this->item->wholesale_price = null;
        } else {
            $this->item->retail_price = $this->retailPrice;
            $this->item->wholesale_price = $this->wholesalePrice;
            $this->item->selling_price_1 = null;
            $this->item->selling_price_2 = null;
        }

        $this->item->save();

        session()->flash('message', 'تم تحديث العنصر بنجاح.');
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
        return view('livewire.Items.item-edit');
    }
}