<?php

namespace App\Http\Livewire\Controllers;

use Livewire\Component;
use Livewire\Attributes\Modelable;

class SelectComponent extends Component
{
    public $data;
    public $placeholder;
    public $has_child;
    public $is_multiple = false;
    public $id;
    public $label;
    public $values;
    public $option_name;

    #[Modelable] 
    public $value;

    public function mount($data, $placeholder, $has_child, $is_multiple = false, $id, $values,$label = '' , $option_name =null)
    {
        $this->label = $label;
        $this->data = $data;
        $this->placeholder = $placeholder;
        $this->has_child = $has_child;
        $this->is_multiple = $is_multiple;
        $this->id = $id;
        $this->values = $values;
        $this->option_name = $option_name;
    }

    public function render()
    {
        return view('components.controllers.select-component');
    }
}
