<?php

namespace App\Http\Livewire\Orders;

use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class OrderView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $searchableFields = ['name'];
    public $perPage = 10;

    public $isSuperAdmin = false;

    public function mount()
    {
        $this->authorize('order-list');
        $this->isSuperAdmin = auth()->id() === 1;
    }

    #[On('destroy')]
    public function destroy($id)
    {
        Order::where('id', $id)->delete();
        return redirect()->route('orders')->with('success', 'Order deleted successfully');
    }

    public function render()
    {
        $user = auth()->user();

        $orders = Order::when(!$this->isSuperAdmin, function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->searchMany($this->searchableFields, $this->search);

        return view('livewire.orders.order-view', [
            'orders' => $orders,
        ]);
    }

    public function generatePdf($orderId)
    {
        return redirect()->route('mpdf.order', ['id' => $orderId]);
    }
}