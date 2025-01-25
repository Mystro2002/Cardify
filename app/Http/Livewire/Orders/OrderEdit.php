<?php

namespace App\Http\Livewire\Orders;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class OrderEdit extends Component
{
    use AuthorizesRequests;

    public $order;
    public $selectedType = 'service';
    public $selectedTypeBackup = 'service';
    public $types = ['service' => 'خدمة', 'product' => 'منتج'];
    public $services;
    public $products;
    public $selectedItem;
    public $quantity = 1;
    public $orderItems = [];
    public $overallTotal = 0;

    public $customers, $campaigns;
    public $selectedCustomer, $selectedCampaign, $note;

    public $name, $salePrice, $marketingAmount, $deliveryAmount, $otherExpenses;

    public $sellingPrice1, $retailPrice;

    protected $rules = [
        'selectedCustomer' => 'required',
        'selectedCampaign' => 'nullable',
        'selectedType' => 'required|in:service,product',
        'note' => 'nullable|string',
    ];

    public function mount($id)
    {
        $this->authorize('order-edit');

        $this->order = Order::findOrFail($id);
        $this->selectedCustomer = $this->order->customer_id;
        $this->selectedCampaign = $this->order->campaign_id;
        $this->selectedType = $this->order->type;
        $this->selectedTypeBackup = $this->order->type;
        $this->note = $this->order->note;

        $this->customers = Customer::all();
        $this->campaigns = Campaign::all();
        $this->products = Item::where('type', 'product')->where('quantity', '>', 0)->get();
        $this->services = Item::where('type', 'service')->where('quantity', '>', 0)->get();

        $this->loadOrderItems();
    }

    public function loadOrderItems()
    {
        $this->orderItems = $this->order->orderDetails->map(function ($detail) {
            $item = $detail->item;
            return [
                'id' => $detail->id,
                'name' => $item->name,
                'quantity' => $detail->quantity,
                'sale_price' => $detail->total / $detail->quantity,
                'marketing_amount' => $detail->marketing_amount,
                'delivery_amount' => $detail->delivery_amount,
                'other_expenses' => $detail->other_expenses,
                'subtotal' => $detail->subtotal,
                'total' => $detail->total,
                'item_id' => $item->id,
            ];
        })->toArray();

        $this->calculateOverallTotal();
    }

    public function updatedSelectedType($value)
    {
        if (($this->selectedTypeBackup == 'product' && $value == 'service') || ($this->selectedTypeBackup == 'service' && $value == 'product')) {
            // Reset item quantities in orderItems
            foreach ($this->orderItems as $item) {
                $originalItem = Item::findOrFail($item['item_id']);
                $originalItem->quantity += $item['quantity']; // Reset the quantity in the database
                $originalItem->save(); // Save the original quantity
            }

            // Clear the order items
            $this->orderItems = [];
            $this->overallTotal = 0;
        }

        $this->selectedTypeBackup = $value;
        $this->selectedType = $value;
        $this->resetItemFields();
    }

    public function updatedSelectedItem($value)
    {
        if ($value) {
            $item = Item::findOrFail($value);
            $this->fillItemFields($item);
        } else {
            $this->resetItemFields();
        }
    }

    public function addItem()
    {
        $this->validate([
            'selectedItem' => 'required',
            'quantity' => 'required|numeric|min:1',
            'retailPrice' => 'nullable|numeric|min:0',
            'sellingPrice1' => 'nullable|numeric|min:0',
        ]);

        $item = Item::findOrFail($this->selectedItem);

        if ($item->quantity < $this->quantity) {
            $this->addError('quantity', 'The quantity you\'ve selected is unavailable.');
            return;
        }

        $this->salePrice = $this->selectedType === 'product' ? $this->retailPrice : $this->sellingPrice1;

        $subtotal = $this->salePrice + $item->marketing_amount + $item->delivery_amount + $item->other_expenses;
        $total = $subtotal * $this->quantity;

        $this->orderItems[] = [
            'name' => $item->name,
            'quantity' => $this->quantity,
            'sale_price' => $this->salePrice,
            'marketing_amount' => $item->marketing_amount,
            'delivery_amount' => $item->delivery_amount,
            'other_expenses' => $item->other_expenses,
            'subtotal' => $subtotal,
            'total' => $total,
            'item_id' => $item->id,
        ];

        $item->quantity -= $this->quantity;
        $item->save();

        $this->calculateOverallTotal();
        $this->resetItemFields();
    }

    public function removeItem($index)
    {
        $item = Item::findOrFail($this->orderItems[$index]['item_id']);
        $item->quantity += $this->orderItems[$index]['quantity'];
        $item->save();

        unset($this->orderItems[$index]);
        $this->orderItems = array_values($this->orderItems);
        $this->calculateOverallTotal();
    }

    public function update()
    {
        $this->validate();

        if (empty($this->orderItems)) {
            $this->addError('orderItems', 'Please add at least one item to the order.');
            return;
        }

        $this->order->update([
            'campaign_id' => $this->selectedCampaign ?: null,
            'customer_id' => $this->selectedCustomer,
            'type' => $this->selectedType,
            'note' => $this->note,
        ]);

        $this->order->orderDetails()->delete();

        foreach ($this->orderItems as $item) {
            OrderDetail::create([
                'order_id' => $this->order->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'other_expenses' => $item['other_expenses'],
                'delivery_amount' => $item['delivery_amount'],
                'marketing_amount' => $item['marketing_amount'],
                'subtotal' => $item['subtotal'],
                'total' => $item['total'],
            ]);
        }

        return redirect()->route('orders')->with('success', 'Order updated successfully.');
    }

    private function resetItemFields()
    {
        $this->reset(['selectedItem', 'name', 'quantity', 'salePrice', 'marketingAmount',
            'deliveryAmount', 'otherExpenses', 'sellingPrice1', 'retailPrice']);
        $this->quantity = 1;
    }

    private function fillItemFields($item)
    {
        $this->name = $item->name;
        $this->marketingAmount = $item->marketing_amount;
        $this->deliveryAmount = $item->delivery_amount;
        $this->otherExpenses = $item->other_expenses;
    }

    private function calculateOverallTotal()
    {
        $this->overallTotal = array_sum(array_column($this->orderItems, 'total'));
    }

    public function render()
    {
        return view('livewire.orders.order-edit');
    }
}