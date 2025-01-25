<?php

namespace App\Http\Livewire\Settings\JobTitles;

use App\Models\JobTitle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class JobTitleView extends Component
{   
    use AuthorizesRequests;
    
    protected $jobTitles;
    public $languages;

    public $search = '';
    public $searchableFields = ['name'];
    protected $paginationTheme = 'bootstrap';

    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('jobtitle-list');

    }

    #[On('destroy')]
    public function destroy($id)
    {
        JobTitle::where('id', $id)->delete();

        return redirect()->route('jobtitles')
            ->with('success', 'Job Title deleted successfully');
    }

    public function render()
    {
        $this->jobTitles = JobTitle::searchMany($this->searchableFields, $this->search);

        return view('livewire.Settings.job-titles.job-title-view',[
            'jobTitles' => $this->jobTitles,
        ]);
    }
}
