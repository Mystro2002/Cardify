<?php

namespace App\Http\Livewire\Content\Categories;

use App\Models\Category;
use App\Models\Language;
use App\Models\Trainer;
use App\Services\MediaManagementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class CategoryAdd extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $name = [];
    public $description = [];
    public $languages = [];
    public $parent_id = null; // Change this to null
    public $selected_categories = [];
    public $src;
    public $slug;
    public $display;
    public $order = 0;
    public $count = 0;
    public $image;

    public $is_free = false;
    public $price;
    public $video;
    public $trainer_id;
    public $trainers = [];

    public $availableCategories;


    public function mount()
    {
        $this->authorize('category-create');

        $this->availableCategories = Category::whereNull('parent_id')->with(
            'details:id,category_id,name',
            'children.details:id,category_id,name'
        )->get();

        $this->trainers = Trainer::active()->get();

        $this->languages = Language::all();
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = '';
            $this->description[$value->id] = '';
        }
    }

    public function store()
    {

        $rules = [
            'parent_id' => 'nullable|exists:categories,id',
            'order' => 'integer',
            'image' => 'required',
            'video' => 'required',
            'is_free' => 'required',
            'trainer_id' => '',
            'price' => 'required_if:is_free,==,false|numeric|min:0',
        ];
        $messages = [
            'percentage.required' => 'Percentage is required',
            'order.required' => 'Order is required',
            'image.required' => 'image is required',
            'image.required' => 'The image is required',
            'video.required' => 'The video is required',
            'price.required' => 'The price is required',
        ];

        foreach ($this->languages as $key => $value) {
            $rules['name.' . $value->id] = 'required';
            $messages['name.' . $value->id] = 'The ' . ucfirst($value->code) . ' name is required';
            $rules['description.' . $value->id] = 'required';
            $messages['description.' . $value->id] = 'The ' . ucfirst($value->code) . ' description is required';
        }
        $validatedData = $this->validate($rules, $messages);

        $path = MediaManagementService::uploadMedia(
            $this->image,
            '/categories',
            env('FILESYSTEM_DRIVER'),
            explode('.', $this->image->getClientOriginalName())[0] . '_' . time() . rand(0, 999999999999) . '.' . $this->image->getClientOriginalExtension()
        );

        $category = new Category();
        $category->parent_id = $validatedData['parent_id'];
        $category->trainer_id = $validatedData['trainer_id'];
        $category->is_free = $validatedData['is_free'];
        $category->price = $validatedData['price'];
        $category->video = $validatedData['video'];
        $category->order = $validatedData['order'];
        $category->image = $path;
        $category->save();

        foreach ($this->languages as $key => $value) {
            $category->details()->create([
                'language_id' => $value->id,
                'name' => $validatedData['name'][$value->id],
                'description' => $validatedData['description'][$value->id],
            ]);
        }

        session()->flash('success', 'Category created successfully.');

        return redirect()->route('categories');
    }

    public function render()
    {
        return view('livewire.Content.categories.category-add');
    }
}
