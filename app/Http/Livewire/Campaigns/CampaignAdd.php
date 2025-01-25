<?php

namespace App\Http\Livewire\Campaigns;

use Livewire\Component;
use App\Models\Campaign;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CampaignAdd extends Component
{
    use AuthorizesRequests;

    public $campaign_name;
    public $launch_date;
    public $duration = null; // Initialize with default values
    public $campaign_balance = null;
    public $daily_amount = 0;
    public $currency_rate = null;

    public function mount()
    {
        $this->authorize('campaign-create');
        $this->calculateDailyAmount();
    }

    public function store()
    {
        $this->validate([
            'campaign_name' => 'required|string|max:255',
            'launch_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'campaign_balance' => 'required|numeric|min:0',
            'daily_amount' => 'required|numeric|min:0', // This ensures the value is a number
            'currency_rate' => 'required|numeric|min:0',
        ]);

        Campaign::create([
            'campaign_name' => $this->campaign_name,
            'launch_date' => $this->launch_date,
            'duration' => $this->duration,
            'campaign_balance' => $this->campaign_balance,
            'currency_rate' => $this->currency_rate,
            'daily_amount' => $this->daily_amount, // Use pre-calculated daily_amount
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', 'Campaign created successfully.');

        return redirect()->route('campaigns');
    }

    public function render()
    {
        return view('livewire.campaigns.campaign-add');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['campaign_balance', 'currency_rate', 'duration'])) {
            $this->calculateDailyAmount();
        }
    }

    public function calculateDailyAmount()
    {
        if ($this->campaign_balance && $this->currency_rate && $this->duration > 0) {
            $this->daily_amount = (float) (($this->campaign_balance * $this->currency_rate) / $this->duration);
        } else {
            $this->daily_amount = 0.0; // Ensure this is a float
        }
    }

}
