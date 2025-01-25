<?php

namespace App\Http\Livewire\Content\Categories;

use App\Models\Category;
use App\Models\Language;
use App\Models\Trainer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class CategoryEdit extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public $name = [];
    public $description = [];
    public $languages = [];
    public $parent_id = null;
    public $src;
    public $slug;
    public $display;
    public $order = 0;
    public $count = 0;
    public $image;
    public $category;
    public $selected_categories = [];
    public $is_free = false;
    public $price;
    public $video;
    public $trainer_id;
    public $trainers = [];

    public $availableCategories;




    public function mount($id)
    {
        $this->authorize('category-edit');
        $this->availableCategories = Category::whereNull('parent_id')->with(
            'details:id,category_id,name',
            'children.details:id,category_id,name'
        )->get();
        $this->languages = Language::all();
        $this->trainers = Trainer::active()->get();
        $this->category = Category::find($id);
        foreach ($this->languages as $key => $value) {
            $this->name[$value->id] = $this->category->details->where('language_id', $value->id)->first()->name ?? null;
            $this->description[$value->id] = $this->category->details->where('language_id', $value->id)->first()->description ?? null;
        }
        $this->parent_id = $this->category->parent_id;
        $this->order = $this->category->order;
        $this->image = $this->category->image;
        $this->trainer_id = $this->category->trainer_id;
        $this->video = $this->category->video;
        $this->is_free = $this->category->is_free == 1 ? true : false;;
        $this->price = $this->category->price;
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

        if ($this->image && !is_string($this->image)) {
            $path = MediaManagementService::checkDeleteUpload(
                $this->category->image,
                $this->image,
                '/categories',
                env('FILESYSTEM_DRIVER'),
                explode('.', $this->image->getClientOriginalName())[0] . '_' . time() . rand(0, 999999999999) . '.' . $this->image->getClientOriginalExtension()
            );
        } else {
            $path = $this->category->image;
        }

        $this->category->parent_id = $validatedData['parent_id'] ?: null;
        $this->category->order =  $validatedData['order'];
        $this->category->trainer_id = $validatedData['trainer_id'];
        $this->category->is_free = $validatedData['is_free'];
        $this->category->price = $validatedData['price'];
        $this->category->video = $validatedData['video'];
        $this->category->order = $validatedData['order'];
        $this->category->image = $path;
        $this->category->save();

        foreach ($this->languages as $key => $value) {
            if ($this->category->details()->where('language_id', $value->id)->exists())
                $this->category->details()->where(
                    [
                        'language_id' => $value->id,
                        'category_id' => $this->category->id
                    ]
                )->update([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id],
                    'description' => $validatedData['description'][$value->id],
                ]);
            else {
                $this->category->details()->create([
                    'language_id' => $value->id,
                    'name' => $validatedData['name'][$value->id],
                    'description' => $validatedData['description'][$value->id],
                ]);
            }
        }

        session()->flash('success', 'Category updated successfully.');

        return redirect()->route('categories');
    }

    public function render()
    {
        return view('livewire.Content.categories.category-edit');
    }
}
