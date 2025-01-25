<?php

namespace App\Http\Livewire\Orders;

use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;

class OrderAdd extends Component
{
    use AuthorizesRequests;

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

    public $sellingPrice1,$retailPrice;

    public function mount()
    {
        $this->authorize('order-create');
        $this->customers = Customer::all();
        $this->campaigns = Campaign::all();
        $this->products = Item::where('type', 'product')->where('quantity', '>', 0)->get();
        $this->services = Item::where('type', 'service')->where('quantity', '>', 0)->get();

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

        // Check if there's enough quantity available
        if ($item->quantity < $this->quantity) {
            $this->addError('quantity', 'The quantity you\'ve selected is unavailable.');
            return;
        }


        // Set the sale price based on the selected type
        $this->salePrice = $this->selectedType === 'product' ? $this->retailPrice : $this->sellingPrice1;


        // Calculate subtotal and total
        $subtotal = $this->salePrice + $item->marketing_amount + $item->delivery_amount + $item->other_expenses;
        $total = $subtotal * $this->quantity;

        // Check if the item already exists in the order
//        $existingItemIndex = $this->findExistingItemIndex($item->id);


            // Add new item to the order
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

        // Decrease the quantity of the item and save
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

    private function findExistingItemIndex($itemId)
    {
        foreach ($this->orderItems as $index => $orderItem) {
            if ($orderItem['item_id'] === $itemId) {
                return $index;
            }
        }
        return false;
    }

    private function resetItemFields()
    {
        $this->reset(['selectedItem', 'name', 'quantity', 'salePrice', 'marketingAmount',
            'deliveryAmount', 'otherExpenses','sellingPrice1','retailPrice']);
        $this->quantity = 1;
    }

    private function fillItemFields($item)
    {
        $this->name = $item->name;
//        $this->salePrice = $this->selectedType == 'product' ? $item->retail_price : $item->selling_price_1;

//        if($this->selectedType==='product'){
//            $this->salePrice=$this->sellingPrice1;
//        }
//        elseif($this->selectedType==='service'){
//            $this->salePrice=$this->retailPrice;
//        }




        $this->marketingAmount = $item->marketing_amount;
        $this->deliveryAmount = $item->delivery_amount;
        $this->otherExpenses = $item->other_expenses;
//        $this->retailPrice = $item->retailPrice;
//        $this->sellingPrice1 = $item->sellingPrice1;
    }

    private function calculateOverallTotal()
    {
        $this->overallTotal = array_sum(array_column($this->orderItems, 'total'));
    }



    public function store()
    {
        $this->validate([
            'selectedCustomer' => 'required',
            'selectedCampaign' => 'nullable',
            'selectedType' => 'required|in:service,product',
        ]);

        if (empty($this->orderItems)) {
            $this->addError('orderItems', 'Please add at least one item to the order.');
            return;
        }

        $order = new Order();
        $order->user_id = auth()->id();
        $order->campaign_id = $this->selectedCampaign ?: null;
        $order->customer_id = $this->selectedCustomer;
        $order->order_nb = 'ORD-' . Str::random(8);
        $order->type = $this->selectedType;
        $order->note = $this->note;
        $order->save();

        foreach ($this->orderItems as $item) {
            $orderDetail = new OrderDetail();
            $orderDetail->order_id = $order->id;
            $orderDetail->item_id = $item['item_id'];
            $orderDetail->quantity = $item['quantity'];
            $orderDetail->other_expenses = $item['other_expenses'];
            $orderDetail->delivery_amount = $item['delivery_amount'];
            $orderDetail->marketing_amount = $item['marketing_amount'];
            $orderDetail->subtotal = $item['subtotal'];
            $orderDetail->total = $item['total'];
            $orderDetail->save();
        }

        return redirect()->route('orders')->with('success', 'Order created successfully.');
    }

    public function render()
    {
        return view('livewire.orders.order-add');
    }
}