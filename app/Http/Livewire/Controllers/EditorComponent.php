<?php

namespace App\Http\Livewire\Controllers;

use Livewire\Component;
use Livewire\Attributes\Modelable;

class EditorComponent extends Component
{

    public $data;
    public $id;
    public $direction;
    public $code;

    #[Modelable]
    public $value;

    public function mount($data, $id, $direction, $code)
    {
        $this->data = $data;
        $this->id = $id;
        $this->direction = $direction;
        $this->code = $code;
        $this->value = $data;
    }

    public function render()
    {
        return view('components.controllers.editor-component');
    }
}
