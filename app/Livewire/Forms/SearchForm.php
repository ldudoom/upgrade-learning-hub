<?php

namespace App\Livewire\Forms;

use Livewire\Form;

class SearchForm extends Form
{
    public string $query = '';

    public function clear()
    {
        $this->reset('query');
    }
}
