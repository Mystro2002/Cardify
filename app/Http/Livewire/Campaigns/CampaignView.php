<?php

namespace App\Http\Livewire\Campaigns;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $campaigns;
    public $selectedUser;

    public $search = '';
    public $searchableFields = ['campaign_name'];

    public $isSuperAdmin = false;


    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('campaign-list');
        $this->isSuperAdmin = auth()->id() === 1;

    }




    #[On('destroy')]
    public function destroy($id)
    {
        Campaign::where('id', $id)->delete();

        return redirect()->route('campaigns')
            ->with('success', 'Campaign deleted successfully');
    }

//    public function render(){
//        $campaigns = Campaign::all();
//
//        return view('livewire.Campaigns.campaign-view', [
//            'campaigns' => $campaigns,
//        ]);
//    }
    public function render()
    {
        $user = auth()->user();
        $campaigns = Campaign::when(!$this->isSuperAdmin , function ($q) use($user){
            $q->where('user_id',$user->id);
        })->searchMany($this->searchableFields, $this->search);

        return view('livewire.Campaigns.campaign-view', [
            'campaigns' => $campaigns,
        ]);
    }




}
