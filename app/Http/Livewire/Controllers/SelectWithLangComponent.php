<?php

namespace App\Http\Livewire\Controllers;

use Livewire\Component;
use Livewire\Attributes\Modelable;

class SelectWithLangComponent extends Component
{
    public $data;
    public $placeholder;
    public $has_child;
    public $is_multiple = false;
    public $id;
    public $values;

    #[Modelable] 
    public $value;

    public function mount($data, $placeholder, $has_child, $is_multiple = false, $id, $values)
    {
        $this->data = $data;
        $this->placeholder = $placeholder;
        $this->has_child = $has_child;
        $this->is_multiple = $is_multiple;
        $this->id = $id;
        $this->values = $values;
    }

    public function render()
    {
        return view('components.controllers.select-with-lang-component');
    }
}
