<?php

namespace App\Http\Livewire\Reports;

use App\Models\Report;
use Livewire\Component;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class ReportView extends Component
{
    use AuthorizesRequests, WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $user;


    public $search = '';
    public $searchableFields = ['name'];
    
    protected $listeners = [
        'destroy',
    ];

    protected $reports;

    public $isSuperAdmin = false;


    public function mount() 
    { 
        $this->authorize('report-list');
        $this->isSuperAdmin = auth()->id() === 1;

    }

    public function updatingSearch()
    {
        $this->resetPage();
    }


    
    #[On('destroy')]
    public function destroy($id)
    {
        Report::where('id', $id)->delete();

        return redirect()->route('reports')
            ->with('success', 'Report deleted successfully');
    }

    public function render()
{
    return view('livewire.Reports.report-view');

}

//    public function render()
//    {
//        $user = auth()->user();
//        $reports = Report::when(!$this->isSuperAdmin , function ($q) use($user){
//            $q->where('user_id',$user->id);
//        })->searchMany($this->searchableFields, $this->search);
//
//        return view('livewire.Reports.report-view', [
//            'reports' => $reports,
//        ]);
//    }
}
