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

class CourseEdit extends Component
{

    use AuthorizesRequests, WithFileUploads;

    public $title = [];
    public $languages = [];
    public $description = [];
    public $is_free = false;
    public $price;
    public $categories = [];
    public $image;
    public $video;
    public $trainer_id;
    public $trainers = [];
    public $category_id;
    public $content;
    public $redirect;

    public function mount($id)
    {
        $this->authorize('course-create');
        $this->languages = Language::all();
        $this->categories = Category::whereNull('parent_id')->with(
            'details:id,category_id,name',
            'children.details:id,category_id,name'
        )->get();
        $this->trainers = Trainer::active()->get();
        $this->content = Content::find($id);
        foreach ($this->languages as $key => $value) {
            $this->title[$value->id] = $this->content->details->where('language_id', $value->id)->first()->title ?? null;
            $this->description[$value->id] = $this->content->details->where('language_id', $value->id)->first()->description ?? null;
        }

        $this->image = $this->content->image;
        $this->video = $this->content->video;
        $this->category_id = $this->content->category_id;
        $this->redirect = $this->content->redirect;
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
            'trainer_id.required' => 'The trainer is required',
        ];

        foreach ($this->languages as $key => $value) {
            $rules['title.' . $value->id] = 'required';
            $messages['title.' . $value->id] = 'The ' . ucfirst($value->code) . ' title is required';
            $rules['description.' . $value->id] = 'required';
            $messages['description.' . $value->id] = 'The ' . ucfirst($value->code) . ' description is required';
        }

        $validatedData = $this->validate($rules, $messages);


        if ($this->image && !is_string($this->image)) {
            $path = MediaManagementService::checkDeleteUpload(
                $this->content->image,
                $this->image,
                '/courses',
                env('FILESYSTEM_DRIVER'),
                explode('.', $this->image->getClientOriginalName())[0] . '_' . time() . rand(0, 999999999999) . '.' . $this->image->getClientOriginalExtension()
            );
        } else {
            $path = $this->content->image;
        }
        $this->content->image = $path;
        $this->content->video = $validatedData['video'];
        $this->content->category_id = $validatedData['category_id'];
        $this->content->redirect = $validatedData['redirect'];
        $this->content->save();

        foreach ($this->languages as $key => $value) {
            if ($this->content->details()->where('language_id', $value->id)->exists())
                $this->content->details()->where(
                    [
                        'language_id' => $value->id,
                        'content_id' => $this->content->id
                    ]
                )->update([
                    'language_id' => $value->id,
                    'title' => $validatedData['title'][$value->id],
                    'description' => $validatedData['description'][$value->id],
                ]);
            else {
                $this->category->details()->create([
                    'language_id' => $value->id,
                    'title' => $validatedData['title'][$value->id],
                    'description' => $validatedData['description'][$value->id],
                ]);
            }
        }

        session()->flash('success', 'Courses created successfully.');
        return redirect()->route('courses');
    }

    public function render()
    {
        return view('livewire.Content.Courses.course-edit');
    }
}
