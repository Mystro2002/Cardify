<?php

namespace App\Http\Livewire\Campaigns;

use Livewire\Component;
use App\Models\Campaign;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CampaignEdit extends Component
{
    use AuthorizesRequests;

    public $campaign;
    public $campaign_name;
    public $launch_date;
    public $duration;
    public $campaign_balance;
    public $daily_amount;
    public $currency_rate;

    public function mount($id)
    {
        $this->authorize('campaign-edit');

        $this->campaign = Campaign::findorfail($id);
        $this->campaign_name = $this->campaign->campaign_name;
        $this->launch_date = $this->campaign->launch_date;
        $this->duration = $this->campaign->duration;
        $this->campaign_balance = $this->campaign->campaign_balance;
        $this->currency_rate = $this->campaign->currency_rate;
        $this->daily_amount = $this->campaign->daily_amount;

        $this->calculateDailyAmount();
    }

    public function update()
    {
        $this->validate([
            'campaign_name' => 'required|string|max:255',
            'launch_date' => 'required|date',
            'duration' => 'required|integer|min:1',
            'campaign_balance' => 'required|numeric|min:0',
            'daily_amount' => 'required|numeric|min:0',
            'currency_rate' => 'required|numeric|min:0',
        ]);

        $this->campaign->update([
            'campaign_name' => $this->campaign_name,
            'launch_date' => $this->launch_date,
            'duration' => $this->duration,
            'campaign_balance' => $this->campaign_balance,
            'currency_rate' => $this->currency_rate,
            'daily_amount' => $this->daily_amount,
        ]);

        session()->flash('success', 'Campaign updated successfully.');

        return redirect()->route('campaigns');
    }

    public function render()
    {
        return view('livewire.campaigns.campaign-edit');
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
