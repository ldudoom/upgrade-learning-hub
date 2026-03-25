<?php

namespace App\Livewire;

use App\Livewire\Forms\SearchForm;
use App\Services\CourseService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class CourseList extends Component
{
    public SearchForm $searchForm;

    /**
     * El método mount() es el constructor de Livewire.
     * Aquí inicializamos el objeto para que no sea null.
     */
    public function mount()
    {
        // Esto evita el error de "uninitialized"
        // Aunque Livewire suele hacerlo solo, forzarlo aquí es más seguro.
        // Inicializamos la propiedad creando una instancia del Form Object
        $this->searchForm = new SearchForm($this, 'searchForm');
    }

    // Forzamos el uso del layout de invitados que no pide usuario logueado
    #[Layout('layouts.app')]
    public function render(CourseService $courseService): View
    {
        return view('livewire.course-list', [
            'courses' => $courseService->getPublishedCourses($this->searchForm->query),
        ]);
    }
}
