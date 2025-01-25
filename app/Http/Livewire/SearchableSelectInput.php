<?php

namespace App\Http\Livewire;

use Livewire\Component;

class SearchableSelectInput extends Component
{
    public $options, $key, $value, $search, $input, $results = [], $model;

    public $initialValue;

    public function mount($options, $key, $value, $model, $initialValue = null)
    {
        $this->options = $options;
        $this->key = $key;
        $this->value = $value;
        $this->results = [];
        $this->model = $model;
        $this->initialValue = $initialValue;

        if ($this->initialValue) {
            $this->setInitialSearch();
        }
    }

    public function search()
    {
        $this->results = [];
        if ($this->search) {
            foreach ($this->options as $option) {
                if (is_array($option[$this->value])) {
                    $target = strtolower($option[$this->value][app()->getLocale()]);
                } else {
                    $target = strtolower($option[$this->value]);
                }
                if (similar_text(strtolower($this->search),
                        $target) == strlen($this->search)) {
                    $this->results[] = $option;
                }
            }
        } else {
            $this->results = [];
        }
    }

    public function changeInputValue($key, $value)
    {
        $this->input = $key;
        $this->search = ucfirst($value);
        $this->results = [];
        $this->emitUp('setModelValue', $this->model, $this->input);
    }

    public function render()
    {
        return view('livewire.searchable-select-input');
    }

    public function setInitialSearch()
    {
        foreach ($this->options as $option) {
            if ($option[$this->key] == $this->initialValue) {
                $this->search = is_array($option[$this->value])
                    ? $option[$this->value][app()->getLocale()]
                    : $option[$this->value];
                break;
            }
        }
    }
}
