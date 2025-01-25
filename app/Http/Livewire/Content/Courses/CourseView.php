<?php

namespace App\Http\Livewire\Content\Courses;

use App\Models\Content;
use App\Models\ContentDetail;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseView extends Component
{
    use AuthorizesRequests;

    public $languages;

    public $search = '';
    public $searchableFields = ['name'];
    protected $paginationTheme = 'bootstrap';
    protected $courses;
    public $dataList;
    public $status = [];

    protected $listeners = [
        'destroy',
    ];

    public function mount()
    {
        $this->authorize('content-list');
        $this->dataList = Content::get();
        foreach ($this->dataList as $data) {
            $this->status[$data->id] = $data->status == 1 ? true : false;
        }
    }

    public function updatedStatus($data) {
        $menuIdsToUpdate = array_keys(array_filter($this->status));
        Content::whereIn('id', $menuIdsToUpdate)->update(['status' => 1]);
        Content::whereNotIn('id', $menuIdsToUpdate)->update(['status' => 0]);
        session()->flash('success', 'Status changed');

    }

    #[On('destroy')]
    public function destroy($id)
    {
        ContentDetail::where('content_id', $id)->delete();
        Content::where('id', $id)->delete();
        return redirect()->route('courses')
            ->with('success', 'Course deleted successfully');
    }

    public function render()
    {
        $this->courses = Content::searchMany($this->searchableFields, $this->search);

        return view(
            'livewire.Content.Courses.course-view',
            ['courses' => $this->courses]
        );
    }
}
