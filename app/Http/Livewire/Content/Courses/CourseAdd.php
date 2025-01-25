<?php

namespace App\Http\Livewire\Content\Courses;

use App\Models\Category;
use App\Models\Content;
use App\Models\Language;
use App\Models\Trainer;
use App\Services\MediaManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

class CourseAdd extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $title = [];
    public $languages = [];
    public $description = [];
    public $categories = [];
    public $image;
    public $video;
    public $category_id;

    public function mount()
    {
        $this->authorize('course-create');
        $this->languages = Language::all();
        $this->categories = Category::whereNull('parent_id')->with(
            'details:id,category_id,name',
            'children.details:id,category_id,name'
        )->get();
        foreach ($this->languages as $key => $value) {
            $this->title[$value->id] = '';
            $this->description[$value->id] = '';
        }
    }
    public function store()
    {
        $rules = [
            'image' => 'required',
            'video' => 'required',
            'category_id' => 'required',
            'redirect' => '',

        ];
        $messages = [
            'image.required' => 'The image is required',
            'video.required' => 'The video is required',
        ];

        foreach ($this->languages as $key => $value) {
            $rules['title.' . $value->id] = 'required';
            $messages['title.' . $value->id] = 'The ' . ucfirst($value->code) . ' title is required';
            $rules['description.' . $value->id] = 'required';
            $messages['description.' . $value->id] = 'The ' . ucfirst($value->code) . ' description is required';
        }

        $validatedData = $this->validate($rules, $messages);

        $path = MediaManagementService::uploadMedia(
            $this->image,
            '/courses',
            env('FILESYSTEM_DRIVER'),
            explode('.', $this->image->getClientOriginalName())[0] . '_' . time() . rand(0, 999999999999) . '.' . $this->image->getClientOriginalExtension()
        );
        $content = new Content();
        $content->image = $path;
        $content->redirect = $validatedData['redirect'];
        $content->video = $validatedData['video'];
        $content->category_id = $validatedData['category_id'];
        $content->save();

        foreach ($this->languages as $key => $value) {
            $content->details()->create([
                'language_id' => $value->id,
                'title' => $validatedData['title'][$value->id],
                'description' => $validatedData['description'][$value->id],
            ]);
        }

        session()->flash('success', 'Courses created successfully.');
        return redirect()->route('courses');
    }

    public function render()
    {
        return view('livewire.Content.Courses.course-add');
    }
}
